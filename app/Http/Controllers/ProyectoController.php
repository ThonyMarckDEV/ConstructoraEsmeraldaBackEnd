<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use App\Models\Fase;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProyectoController extends Controller
{
    public function index()
    {
        try {
            $proyectos = Proyecto::with(['encargado.datos', 'cliente.datos', 'fases'])->get();
            return response()->json($proyectos, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al cargar proyectos: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin_estimada' => 'required|date|after_or_equal:fecha_inicio',
            'estado' => 'required|in:En Progreso,Finalizado',
            'fase' => 'required|in:Planificación,Preparación del Terreno,Construcción de Cimientos,Estructura y Superestructura,Instalaciones,Acabados,Inspección y Pruebas,Entrega',
            'idEncargado' => 'required|exists:usuarios,idUsuario',
            'idCliente' => 'required|exists:usuarios,idUsuario',
            'fases' => 'nullable|array',
            'fases.*.nombreFase' => 'required|in:Planificación,Preparación del Terreno,Construcción de Cimientos,Estructura y Superestructura,Instalaciones,Acabados,Inspección y Pruebas,Entrega',
            'fases.*.fecha_inicio' => 'nullable|date',
            'fases.*.fecha_fin' => 'nullable|date|after_or_equal:fases.*.fecha_inicio',
            'fases.*.descripcion' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validación fallida', 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $encargado = User::where('idUsuario', $request->idEncargado)
                ->where('idRol', 3) // idRol 3 = manager
                ->firstOrFail();
            $cliente = User::where('idUsuario', $request->idCliente)
                ->where('idRol', 2) // idRol 2 = cliente
                ->firstOrFail();

            $proyecto = Proyecto::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin_estimada' => $request->fecha_fin_estimada,
                'estado' => $request->estado,
                'fase' => $request->fase,
                'idEncargado' => $request->idEncargado,
                'idCliente' => $request->idCliente,
            ]);

            if ($request->has('fases')) {
                foreach ($request->fases as $faseData) {
                    if ($faseData['fecha_inicio'] || $faseData['fecha_fin'] || $faseData['descripcion']) {
                        Fase::create([
                            'idProyecto' => $proyecto->idProyecto,
                            'nombreFase' => $faseData['nombreFase'],
                            'fecha_inicio' => $faseData['fecha_inicio'],
                            'fecha_fin' => $faseData['fecha_fin'],
                            'descripcion' => $faseData['descripcion'],
                        ]);
                    }
                }
            }

            Chat::create([
                'idCliente' => $request->idCliente,
                'idEncargado' => $request->idEncargado,
                'idProyecto' => $proyecto->idProyecto,
            ]);

            DB::commit();
            return response()->json(['message' => 'Proyecto creado exitosamente', 'proyecto' => $proyecto->load(['fases', 'encargado.datos', 'cliente.datos'])], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al crear proyecto: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $proyecto = Proyecto::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin_estimada' => 'required|date|after_or_equal:fecha_inicio',
            'estado' => 'required|in:En Progreso,Finalizado',
            'fase' => 'required|in:Planificación,Preparación del Terreno,Construcción de Cimientos,Estructura y Superestructura,Instalaciones,Acabados,Inspección y Pruebas,Entrega',
            'idEncargado' => 'required|exists:usuarios,idUsuario',
            'idCliente' => 'required|exists:usuarios,idUsuario',
            'fases' => 'nullable|array',
            'fases.*.nombreFase' => 'required|in:Planificación,Preparación del Terreno,Construcción de Cimientos,Estructura y Superestructura,Instalaciones,Acabados,Inspección y Pruebas,Entrega',
            'fases.*.fecha_inicio' => 'nullable|date',
            'fases.*.fecha_fin' => 'nullable|date|after_or_equal:fases.*.fecha_inicio',
            'fases.*.descripcion' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validación fallida', 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $encargado = User::where('idUsuario', $request->idEncargado)
                ->where('idRol', 3) // idRol 3 = manager
                ->firstOrFail();
            $cliente = User::where('idUsuario', $request->idCliente)
                ->where('idRol', 2) // idRol 2 = cliente
                ->firstOrFail();

            $proyecto->update([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin_estimada' => $request->fecha_fin_estimada,
                'estado' => $request->estado,
                'fase' => $request->fase,
                'idEncargado' => $request->idEncargado,
                'idCliente' => $request->idCliente,
            ]);

            if ($request->has('fases')) {
                Fase::where('idProyecto', $proyecto->idProyecto)->delete();
                foreach ($request->fases as $faseData) {
                    if ($faseData['fecha_inicio'] || $faseData['fecha_fin'] || $faseData['descripcion']) {
                        Fase::create([
                            'idProyecto' => $proyecto->idProyecto,
                            'nombreFase' => $faseData['nombreFase'],
                            'fecha_inicio' => $faseData['fecha_inicio'],
                            'fecha_fin' => $faseData['fecha_fin'],
                            'descripcion' => $faseData['descripcion'],
                        ]);
                    }
                }
            }
            
             // Update or create the chat associated with the project
            $chat = Chat::where('idProyecto', $proyecto->idProyecto)->first();
            if ($chat) {
                $chat->update([
                    'idCliente' => $request->idCliente,
                    'idEncargado' => $request->idEncargado,
                ]);
            } else {
                Chat::create([
                    'idCliente' => $request->idCliente,
                    'idEncargado' => $request->idEncargado,
                    'idProyecto' => $proyecto->idProyecto,
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Proyecto actualizado exitosamente', 'proyecto' => $proyecto->load(['fases', 'encargado.datos', 'cliente.datos'])], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al actualizar proyecto: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $proyecto = Proyecto::findOrFail($id);
            DB::beginTransaction();
            Chat::where('idProyecto', $id)->delete();
            $proyecto->delete();
            DB::commit();
            return response()->json(['message' => 'Proyecto eliminado exitosamente'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al eliminar proyecto: ' . $e->getMessage()], 500);
        }
    }

    public function getEncargados()
    {
        try {
            $encargados = User::where('idRol', 3) // idRol 3 = manager
                ->with(['datos', 'rol'])
                ->get();
            return response()->json($encargados, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al cargar encargados: ' . $e->getMessage()], 500);
        }
    }

    public function getClientes()
    {
        try {
            $clientes = User::where('idRol', 2) // idRol 2 = cliente
                ->with(['datos', 'rol'])
                ->get();
            return response()->json($clientes, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al cargar clientes: ' . $e->getMessage()], 500);
        }
    }

    public function getChatIdByProyecto(Request $request, $proyectoId)
    {
        try {
            // Find the chat associated with the project ID
            $chat = Chat::where('idProyecto', $proyectoId)->first();

            if (!$chat) {
                return response()->json([
                    'message' => 'No se encontró un chat asociado con el proyecto especificado.'
                ], 404);
            }

            return response()->json([
                'idChat' => $chat->idChat
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener el ID del chat.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}