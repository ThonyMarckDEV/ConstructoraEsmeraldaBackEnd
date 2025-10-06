<?php

namespace App\Http\Controllers;

use App\Models\Datos;
use App\Models\Log;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{


    //CRUD OF CLIENT 
      /**
     * Display a listing of the clients.
     */
    public function index()
    {
        try {
            // Obtener todos los usuarios con rol de cliente (idRol = 2)
            $clientes = User::with('datos')
                          ->where('idRol', 2)
                          ->orderBy('created_at', 'desc')
                          ->get();
            
            return response()->json($clientes);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener clientes', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created client in storage.
     */
    public function store(Request $request)
    {
        // Validar los datos de entrada
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:datos,email',
            'telefono' => 'required|string|max:20',
            'username' => 'required|string|max:255|unique:usuarios,username',
            'password' => 'required|string|min:6',
            'dni' => 'nullable|string|max:20',
            'ruc' => 'nullable|string|max:20',
            'direccion' => 'nullable|string',
            'estado' => 'required|in:activo,inactivo'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Error de validación', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // Crear datos personales
            $datos = Datos::create([
                'nombre' => $request->nombre,
                'apellido' => $request->apellido,
                'email' => $request->email,
                'telefono' => $request->telefono,
                'dni' => $request->dni,
                'ruc' => $request->ruc,
                'direccion' => $request->direccion
            ]);

            // Crear usuario
            $usuario = User::create([
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'idDatos' => $datos->idDatos,
                'idRol' => 2, // Rol de cliente
                'estado' => $request->estado
            ]);

            DB::commit();

            // 2. Obtén el ID del usuario autenticado
            $usuarioId = Auth::id();
            
            // 3. Crea el registro en la tabla de logs
            Log::create([
                'id_Usuario' => $usuarioId,
                'registro' => 'Creo un nuevo cliente'
            ]);

            return response()->json([
                'message' => 'Cliente creado exitosamente',
                'cliente' => [
                    'usuario' => $usuario,
                    'datos' => $datos
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al crear cliente', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified client.
     */
    public function show($id)
    {
        try {
            $cliente = User::with('datos')
                         ->where('idRol', 2)
                         ->where('idUsuario', $id)
                         ->firstOrFail();
            
            return response()->json($cliente);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Cliente no encontrado'], 404);
        }
    }

    /**
     * Update the specified client in storage.
     */
    public function update(Request $request, $id)
    {
        // Obtener el cliente
        $cliente = User::where('idRol', 2)
                   ->where('idUsuario', $id)
                   ->first();

        if (!$cliente) {
            return response()->json(['message' => 'Cliente no encontrado'], 404);
        }

        // Reglas de validación
        $rules = [
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:datos,email,' . $cliente->idDatos . ',idDatos',
            'telefono' => 'required|string|max:20',
            'username' => 'required|string|max:255|unique:usuarios,username,' . $id . ',idUsuario',
            'dni' => 'nullable|string|max:20',
            'ruc' => 'nullable|string|max:20',
            'direccion' => 'nullable|string',
            'estado' => 'required|in:activo,inactivo'
        ];

        // Validar contraseña solo si se proporciona
        if ($request->filled('password')) {
            $rules['password'] = 'string|min:6';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['message' => 'Error de validación', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // Actualizar datos personales
            $datos = Datos::find($cliente->idDatos);
            
            if ($datos) {
                $datos->update([
                    'nombre' => $request->nombre,
                    'apellido' => $request->apellido,
                    'email' => $request->email,
                    'telefono' => $request->telefono,
                    'dni' => $request->dni,
                    'ruc' => $request->ruc,
                    'direccion' => $request->direccion
                ]);
            }

            // Actualizar datos de usuario
            $clienteData = [
                'username' => $request->username,
                'estado' => $request->estado
            ];

            // Actualizar contraseña solo si se proporciona
            if ($request->filled('password')) {
                $clienteData['password'] = Hash::make($request->password);
            }

            $cliente->update($clienteData);

            DB::commit();

            // 2. Obtén el ID del usuario autenticado
            $usuarioId = Auth::id();
            
            // 3. Crea el registro en la tabla de logs
            Log::create([
                'id_Usuario' => $usuarioId,
                'registro' => 'Actualizo un cliente'
            ]);

            return response()->json([
                'message' => 'Cliente actualizado exitosamente',
                'cliente' => [
                    'usuario' => $cliente->fresh(),
                    'datos' => $datos->fresh()
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al actualizar cliente', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified client from storage.
     */
    public function destroy($id)
    {
        try {
            $cliente = User::where('idRol', 2)
                         ->where('idUsuario', $id)
                         ->firstOrFail();
            
            // Check for active projects
            $activeProject = Proyecto::where('idCliente', $id)
                                    ->where('estado', 'En Progreso')
                                    ->exists();
            
            if ($activeProject) {
                return response()->json([
                    'message' => 'No se puede desactivar el cliente porque está asociado a un proyecto en progreso. Por favor, finalice el proyecto e intente de nuevo.'
                ], 400);
            }
            
            // Soft delete by changing estado to inactivo
            $cliente->update(['estado' => 'inactivo']);
            
            return response()->json(['message' => 'Cliente desactivado exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar cliente', 'error' => $e->getMessage()], 500);
        }
    }


}