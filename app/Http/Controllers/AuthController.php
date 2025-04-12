<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTFactory;


class AuthController extends Controller
{
   
    public function login(Request $request)
    {
        // Validar los datos de la solicitud
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
            'remember_me' => 'boolean',  // Si piden un refresh para ser recordado dura 7 dias
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
        
        // Verificar si el estado del usuario es activo
        if ($user->estado !== 'activo') {
            return response()->json([
                'message' => 'Error: estado del usuario inactivo',
            ], 403);
        }
        
        // Generar token de acceso con Firebase JWT (5minutos)
        $now = time();
        $expiresIn = config('jwt.ttl') * 60;


        // Generar token de refresco si esta rememberMe
        $rememberMe = $request->remember_me ?? false;
        $refreshTTL = $rememberMe 
            ? 7 * 24 * 60 * 60       // 7 days if remember_me is true
            : 1 * 24 * 60 * 60;      // 1 day if remember_me is false
        $secret = config('jwt.secret');
        
        // Access token con custom claims del usuario
        $accessPayload = [
            'iss' => config('app.url'),
            'iat' => $now,
            'exp' => $now + $expiresIn,
            'nbf' => $now,
            'jti' => Str::random(16),
            'sub' => $user->idUsuario,
            'prv' => sha1(config('app.key')),
            // Custom claims del modelo usuario
            'rol' => $user->rol->nombre,
            'username' => $user->username,
            // Otros atributos del usuario que quieras incluir
            'nombre' => $user->datos->nombre, 
            'email' => $user->datos->email
        ];
        
        // Refresh token (más simple, sin custom claims)
        $refreshPayload = [
            'iss' => config('app.url'),
            'iat' => $now,
            'exp' => $now + $refreshTTL,  // Use the dynamic value here
            'nbf' => $now,
            'jti' => Str::random(16),
            'sub' => $user->idUsuario,
            'prv' => sha1(config('app.key')),
            'type' => 'refresh',
            'rol' => $user->rol->nombre,
        ];
        
        // Generar tokens usando Firebase JWT
        $accessToken = \Firebase\JWT\JWT::encode($accessPayload, $secret, 'HS256');
        $refreshToken = \Firebase\JWT\JWT::encode($refreshPayload, $secret, 'HS256');
        
        // Devolver la respuesta con los tokens
        return response()->json([
            'message' => 'Login exitoso',
            'access_token' => $accessToken,
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
            // Verificar el token con Firebase JWT
            $secret = config('jwt.secret');
            $payload = \Firebase\JWT\JWT::decode($request->refresh_token, new \Firebase\JWT\Key($secret, 'HS256'));
            
            // Verificar que sea un token de refresco
            if (!isset($payload->type) || $payload->type !== 'refresh') {
                return response()->json([
                    'message' => 'El token proporcionado no es un token de refresco',
                ], 401);
            }
            
            // Obtener el ID de usuario
            $userId = $payload->sub;
            $user = User::find($userId);
            
            if (!$user) {
                return response()->json([
                    'message' => 'Usuario no encontrado',
                ], 404);
            }
            
            // Generar un nuevo token de acceso con Firebase JWT
            $now = time();
            $expiresIn = config('jwt.ttl') * 60;
            
            // Crear payload del token de acceso con custom claims del usuario
            $accessPayload = [
                'iss' => config('app.url'),
                'iat' => $now,
                'exp' => $now + $expiresIn,
                'nbf' => $now,
                'jti' => Str::random(16),
                'sub' => $user->idUsuario,
                'prv' => sha1(config('app.key')),
                // Custom claims del usuario
                'rol' => $user->rol->nombre,
                'username' => $user->username,
                // Otros atributos del usuario que quieras incluir
                'nombre' => $user->datos->nombre, 
                'email' => $user->datos->email,
            ];
            
            // Generar nuevo token de acceso usando Firebase JWT
            $newToken = \Firebase\JWT\JWT::encode($accessPayload, $secret, 'HS256');
            
            return response()->json([
                'message' => 'Token actualizado',
                'access_token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => $expiresIn
            ], 200);
            
        } catch (\Firebase\JWT\ExpiredException $e) {
            return response()->json([
                'message' => 'Refresh token expirado'
            ], 401);
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            return response()->json([
                'message' => 'Refresh token inválido'
            ], 401);
        } catch (\Exception $e) {
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
}