<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;
use Illuminate\Support\Facades\Log;

class CheckRolesMW
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            // Log del token recibido
          //  Log::info('Token recibido:', ['token' => $request->header('Authorization')]);
            
            $token = JWTAuth::parseToken();
            $payload = $token->getPayload();
            
            // Log del payload completo
           // Log::info('Payload completo:', ['payload' => $payload->toArray()]);
            
            $userRole = $payload->get('rol');

            // Definir los roles permitidos manualmente
            $allowedRoles = ['admin','manager','cliente']; // Roles permitidos

            // Log para verificar los roles permitidos y el rol del usuario
          //  Log::info('Roles permitidos: ' . implode(', ', $allowedRoles));
           // Log::info('Rol del usuario obtenido del token: ' . $userRole);

            // Verificar si el rol del usuario está dentro de los roles permitidos
            if (!in_array($userRole, $allowedRoles)) {
                // Log para ver los detalles del error
             //   Log::info('Acceso denegado: El rol del usuario no coincide con los roles permitidos.');
                return response()->json(['error' => 'Acceso denegado: El rol del usuario no coincide con los roles permitidos.'], 403);
            }

        } catch (Exception $e) {
            return response()->json(['error' => 'Token inválido o no proporcionado.'], 401);
        }

        // Si el rol es correcto, continuar con la solicitud
        return $next($request);
    }
}
