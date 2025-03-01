<?php

namespace App\Http\Controllers\TravelOrder;

use App\Http\Controllers\Controller;
use App\Http\Requests\TravelOrder\UpdateTravelOrderRequest;
use App\Models\TravelOrder;
use App\Notifications\TravelOrderStatusNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TravelOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Validação manual
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|string|in:solicitado,aprovado,cancelado',
            'destination' => 'nullable|string|max:255',
            'departure_date' => 'nullable|date',
            'return_date' => 'nullable|date|after_or_equal:departure_date',
        ], [
            'status.in' => 'O status deve ser um dos seguintes: solicitado, aprovado, cancelado.',
            'destination.max' => 'O destino não pode ter mais de 255 caracteres.',
            'departure_date.date' => 'A data de partida deve ser uma data válida.',
            'return_date.date' => 'A data de retorno deve ser uma data válida.',
            'return_date.after_or_equal' => 'A data de retorno deve ser igual ou posterior à data de partida.',
        ]);

        // Se a validação falhar, retorne os erros
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro de validação',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $query = Auth::user()->travelOrders();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('destination')) {
            $query->where('destination', 'like', '%' . $request->destination . '%');
        }

        if ($request->has('departure_date') && $request->has('return_date')) {
            $travelStartDate = Carbon::parse($request->departure_date)->startOfDay();
            $travelEndDate = Carbon::parse($request->return_date)->endOfDay();

            $query->where(function ($q) use ($travelStartDate, $travelEndDate) {
                $q->where(function ($q) use ($travelStartDate, $travelEndDate) {
                      $q->where('departure_date', '<=', $travelEndDate)
                        ->where('return_date', '>=', $travelStartDate);
                  });
            });
        }

        $query->orderBy('created_at', 'desc');
        $travelOrders = $query->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => [
                'travelOrders' => $travelOrders,
            ],
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'destination' => 'required|string|max:255',
            'departure_date' => 'required|date',
            'return_date' => 'required|date|after_or_equal:departure_date',
        ], [
            'destination.required' => 'O destino é obrigatório.',
            'destination.max' => 'O destino não pode ter mais de 255 caracteres.',
            'departure_date.required' => 'A data de partida é obrigatória.',
            'departure_date.date' => 'A data de partida deve ser uma data válida.',
            'return_date.required' => 'A data de retorno é obrigatória.',
            'return_date.date' => 'A data de retorno deve ser uma data válida.',
            'return_date.after_or_equal' => 'A data de retorno deve ser igual ou posterior à data de partida.',
        ]);
    
        // Se a validação falhar, retorne os erros
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro de validação',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Verifica se há conflitos com pedidos existentes
        $conflictingOrders = $this->checkForConflictingOrders(
            $request->departure_date,
            $request->return_date
        );

        if ($conflictingOrders->isNotEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Date conflict with existing orders.',
                'conflicts' => $conflictingOrders,
            ], 409);
        }

        $request->merge(['status' => 'solicitado']);

        $travelOrder = Auth::user()->travelOrders()->create($request->all());

        return response()->json([
            'status' => 'success',
            'data' => [
                'travelOrder' => $travelOrder,
            ],
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(TravelOrder $travelOrder)
    {
        $travelOrder = Auth::user()->travelOrders()->find($travelOrder->id);

        if (!$travelOrder) {
            return response()->json([
                'status' => 'error',
                'message' => 'Travel order not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'travelOrder' => $travelOrder,
            ],
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTravelOrderRequest $request, TravelOrder $travelOrder)
{
        // Verifica se o pedido pertence ao usuário autenticado
        if ($travelOrder->user_id !== Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to change this order.',
            ], 403);
        }

        if ($request->status === 'cancelado' && $travelOrder->status !== 'aprovado') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only approved orders can be cancelled.',
            ], 422);
        }

        if ($request->status === 'aprovado' && $travelOrder->status === 'aprovado') {
            return response()->json([
                'status' => 'error',
                'message' => 'This request has already been approved.',
            ], 422);
        }

        // Atualiza apenas o status
        $travelOrder->update([
            'status' => $request->status,
        ]);

        if (in_array($request->status, ['aprovado', 'cancelado'])) {
            $travelOrder->user->notify(new TravelOrderStatusNotification($travelOrder, $request->status));
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'travelOrder' => $travelOrder,
            ],
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TravelOrder $travelOrder)
    {
        $travelOrder = Auth::user()->travelOrders()->find($travelOrder->id);

        if (!$travelOrder) {
            return response()->json([
                'status' => 'error',
                'message' => 'Travel order not found',
            ], 404);
        }

        $travelOrder->delete();

        return response()->json([
            'status' => 'success',
            'data' => [
                'travelOrder' => $travelOrder,
            ],
        ], 201);
    }

    /**
     * Verifica se há conflitos com pedidos existentes.
     */
    private function checkForConflictingOrders($departureDate, $returnDate)
    {
        return Auth::user()->travelOrders()
            ->whereIn('status', ['solicitado', 'aprovado'])
            ->where(function ($query) use ($departureDate, $returnDate) {
                $query->where(function ($q) use ($departureDate, $returnDate) {
                    $q->where('departure_date', '<=', $returnDate)
                    ->where('return_date', '>=', $departureDate);
                });
            })
            ->get();
    }
}
