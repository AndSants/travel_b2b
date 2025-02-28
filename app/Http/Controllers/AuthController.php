<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Registra um novo usuário.
     */
    public function register(Request $request)
    {
        // Validação dos dados de entrada
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        // Retorna erros de validação, se houver
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Cria o usuário
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        // Gera o token de acesso
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'status' => 'success',
            'message' => 'User registered successfully',
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
                'refresh_token' => $this->createRefreshToken($user),
                'refresh_token_expires_in' => config('jwt.refresh_ttl') * 60,
            ],
        ], 201);
    }

    /**
     * Autentica o usuário e retorna um token JWT.
     */
    public function login(Request $request)
    {
        // Validação dos dados de entrada
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // Retorna erros de validação, se houver
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Tenta autenticar o usuário
        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Retorna o token de acesso e o refresh token
        return $this->respondWithToken($token);
    }

    /**
     * Retorna o usuário autenticado.
     */
    public function me()
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => Auth::user(),
            ],
        ]);
    }

    /**
     * Renova o token de acesso usando o refresh token.
     */
    public function refresh(Request $request)
    {
        $refreshToken = $request->input('refresh_token');

        try {
            // Verifica se o refresh token é válido
            $payload = JWTAuth::setToken($refreshToken)->getPayload();

            if ($payload->get('type') !== 'refresh') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid refresh token',
                ], 401);
            }

            // Verifica se o refresh token expirou
            if (Carbon::now()->gt($payload->get('exp'))) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Refresh token expired',
                ], 401);
            }

            // Gera um novo token de acesso
            $newToken = JWTAuth::parseToken()->refresh();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'access_token' => $newToken,
                    'token_type' => 'bearer',
                    'expires_in' => JWTAuth::factory()->getTTL() * 60,
                ],
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to refresh token',
            ], 401);
        }
    }

    /**
     * Desloga o usuário (invalida o token).
     */
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully logged out',
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to logout, please try again',
            ], 500);
        }
    }

    /**
     * Retorna a estrutura do token JWT.
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
                'refresh_token' => $this->createRefreshToken(Auth::user()),
                'refresh_token_expires_in' => config('jwt.refresh_ttl') * 60,
            ],
        ]);
    }

    /**
     * Cria um refresh token com tempo de expiração personalizado.
     */
    protected function createRefreshToken($user)
    {
        return JWTAuth::customClaims([
            'type' => 'refresh',
            'exp' => time() + config('jwt.refresh_ttl'),
        ])->fromUser($user);
    }
}