<?php

namespace App\Http\Controllers;

use App\Models\Datos;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ManagerController extends Controller
{
    /**
     * Display a listing of the supervisors.
     */
    public function index()
    {
        try {
            // Obtener todos los usuarios con rol de encargado (idRol = 3)
            $encargados = User::with('datos')
                          ->where('idRol', 3)
                          ->orderBy('created_at', 'desc')
                          ->get();
            
            return response()->json($encargados);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener encargados', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created supervisor in storage.
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
                'idRol' => 3, // Rol de encargado
                'estado' => $request->estado
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Encargado creado exitosamente',
                'encargado' => [
                    'usuario' => $usuario,
                    'datos' => $datos
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al crear encargado', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified supervisor.
     */
    public function show($id)
    {
        try {
            $encargado = User::with('datos')
                         ->where('idRol', 3)
                         ->where('idUsuario', $id)
                         ->firstOrFail();
            
            return response()->json($encargado);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Encargado no encontrado'], 404);
        }
    }

    /**
     * Update the specified supervisor in storage.
     */
    public function update(Request $request, $id)
    {
        // Obtener el encargado
        $encargado = User::where('idRol', 3)
                   ->where('idUsuario', $id)
                   ->first();

        if (!$encargado) {
            return response()->json(['message' => 'Encargado no encontrado'], 404);
        }

        // Reglas de validación
        $rules = [
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:datos,email,' . $encargado->idDatos . ',idDatos',
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
            $datos = Datos::find($encargado->idDatos);
            
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
            $encargadoData = [
                'username' => $request->username,
                'estado' => $request->estado
            ];

            // Actualizar contraseña solo si se proporciona
            if ($request->filled('password')) {
                $encargadoData['password'] = Hash::make($request->password);
            }

            $encargado->update($encargadoData);

            DB::commit();

            return response()->json([
                'message' => 'Encargado actualizado exitosamente',
                'encargado' => [
                    'usuario' => $encargado->fresh(),
                    'datos' => $datos->fresh()
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al actualizar encargado', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified supervisor from storage.
     */
    public function destroy($id)
    {
        try {
            $encargado = User::where('idRol', 3)
                         ->where('idUsuario', $id)
                         ->firstOrFail();
            
            // Check for active projects
            $activeProject = Proyecto::where('idEncargado', $id)
                                    ->where('estado', 'En Progreso')
                                    ->exists();
            
            if ($activeProject) {
                return response()->json([
                    'message' => 'No se puede desactivar el encargado porque está asociado a un proyecto en progreso. Por favor, finalice el proyecto e intente de nuevo.'
                ], 400);
            }
            
            // Soft delete by changing estado to inactivo
            $encargado->update(['estado' => 'inactivo']);
            
            return response()->json(['message' => 'Encargado desactivado exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar encargado', 'error' => $e->getMessage()], 500);
        }
    }
}