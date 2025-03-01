<?php

namespace App\Http\Controllers\TravelOrder;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTravelOrderRequest;
use App\Http\Requests\UpdateTravelOrderRequest;
use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class TravelOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $travelOrders = Auth::user()->travelOrders()->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'travelOrders' => $travelOrders,
            ],
        ], 201);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTravelOrderRequest $request)
    {
        // Verifica se há conflitos com pedidos existentes
        $conflictingOrders = $this->checkForConflictingOrders(
            $request->departure_date,
            $request->return_date
        );

        if ($conflictingOrders->isNotEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Conflito de datas com pedidos existentes.',
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
                'message' => 'Você não tem permissão para alterar este pedido.',
            ], 403);
        }

        // Atualiza apenas o status
        $travelOrder->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'travelOrder' => $travelOrder,
            ],
        ], 200); // 200 OK
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
