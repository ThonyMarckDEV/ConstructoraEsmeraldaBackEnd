<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    // Login del usuario
    public function login(Request $request)
    {
        // Validar los datos de la solicitud
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 400);
        }

        // Buscar el usuario por su 'username'
        $user = User::where('username', $request->username)->first();

        // Si el usuario no existe o la contraseña no es válida
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Usuario o contraseña incorrectos',
            ], 401);
        }

        // Generar el token JWT para el usuario
        $token = JWTAuth::fromUser($user);
        
        // Obtener el tiempo de expiración desde la configuración
        $expiresIn = config('jwt.ttl') * 60;
        
        // Crear un token de refresco (usando el mismo TTL pero más largo)
        $refreshTTL = config('jwt.refresh_ttl');

        // Almacenar una reclamación personalizada que nos permita identificar este token como un refresh token
        $refreshPayload = [
            'sub' => $user->idUsuario,
            'type' => 'refresh',
            'exp' => now()->addMinutes($refreshTTL)->timestamp
        ];
        
        $refreshToken = JWTAuth::customClaims($refreshPayload)->fromUser($user);

        // Devolver la respuesta con los tokens
        return response()->json([
            'message' => 'Login exitoso',
            'access_token' => $token,
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
            'expires_in' => $expiresIn
        ], 200);
    }

    // Método para refrescar el token
    public function refresh(Request $request)
    {
        // Validar el refresh token
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Refresh token inválido',
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            // Establecer el token de refresco como el token actual
            JWTAuth::setToken($request->refresh_token);
            
            // Verificar el token y obtener el payload
            $payload = JWTAuth::getPayload();
            
            // Verificar que sea un token de refresco
            if (!$payload->get('type') || $payload->get('type') !== 'refresh') {
                return response()->json([
                    'message' => 'El token proporcionado no es un token de refresco',
                ], 401);
            }
            
            // Obtener el ID de usuario
            $userId = $payload->get('sub');
            $user = User::find($userId);
            
            if (!$user) {
                return response()->json([
                    'message' => 'Usuario no encontrado',
                ], 404);
            }
            
            // Generar un nuevo token de acceso
            $newToken = JWTAuth::fromUser($user);
            
            // Obtener el tiempo de expiración
            $expiresIn = config('jwt.ttl') * 60;
            
            return response()->json([
                'message' => 'Token actualizado',
                'access_token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => $expiresIn
            ], 200);
            
        } catch (TokenExpiredException $e) {
            return response()->json([
                'message' => 'Refresh token expirado'
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'message' => 'Refresh token inválido'
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Error al procesar el token',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // Método para cerrar sesión
    public function logout()
    {
        try {
            // Invalidar el token actual
            JWTAuth::invalidate(JWTAuth::getToken());
            
            return response()->json([
                'message' => 'Sesión cerrada exitosamente'
            ], 200);
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Error al cerrar sesión',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // Método para obtener el usuario autenticado
    public function me()
    {
        try {
            // Obtener el usuario autenticado
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Usuario no encontrado'
                ], 404);
            }
            
            return response()->json([
                'user' => $user
            ], 200);
            
        } catch (TokenExpiredException $e) {
            return response()->json([
                'message' => 'Token expirado'
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'message' => 'Token inválido'
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Token no proporcionado',
                'error' => $e->getMessage()
            ], 401);
        }
    }
}