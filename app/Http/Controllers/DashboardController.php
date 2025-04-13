<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
  
    public function getAnalytics($idEncargado)
    {
        try {
            // Validate the encargado exists
            $encargado = User::findOrFail($idEncargado);
            
            // Get all projects assigned to this manager
            $proyectos = Proyecto::where('idEncargado', $idEncargado)->get();
            
            // Count total projects
            $totalProyectos = $proyectos->count();
            
            // Get projects by status
            $proyectosPorEstado = [
                'En Progreso' => $proyectos->where('estado', 'En Progreso')->count(),
                'Finalizado' => $proyectos->where('estado', 'Finalizado')->count()
            ];
            
            // Get projects by phase
            $proyectosPorFase = [
                'Planificación' => $proyectos->where('fase', 'Planificación')->count(),
                'Preparación del Terreno' => $proyectos->where('fase', 'Preparación del Terreno')->count(),
                'Construcción de Cimientos' => $proyectos->where('fase', 'Construcción de Cimientos')->count(),
                'Estructura y Superestructura' => $proyectos->where('fase', 'Estructura y Superestructura')->count(),
                'Instalaciones' => $proyectos->where('fase', 'Instalaciones')->count(),
                'Acabados' => $proyectos->where('fase', 'Acabados')->count(),
                'Inspección y Pruebas' => $proyectos->where('fase', 'Inspección y Pruebas')->count(),
                'Entrega' => $proyectos->where('fase', 'Entrega')->count()
            ];
            
            // Remove phases with zero projects
            $proyectosPorFase = array_filter($proyectosPorFase, function($count) {
                return $count > 0;
            });
            
            // Prepare timeline data including project names, start dates, and durations
            $proyectosTimeline = $proyectos->map(function($proyecto) {
                $startDate = new \DateTime($proyecto->fecha_inicio);
                $endDate = new \DateTime($proyecto->fecha_fin_estimada);
                $duration = $startDate->diff($endDate)->days / 30; // Converting to months
                
                return [
                    'id' => $proyecto->idProyecto,
                    'nombre' => $proyecto->nombre,
                    'fecha_inicio' => $proyecto->fecha_inicio,
                    'fecha_fin_estimada' => $proyecto->fecha_fin_estimada,
                    'estado' => $proyecto->estado,
                    'fase' => $proyecto->fase,
                    'duration' => round($duration, 1) // Round to 1 decimal place
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_proyectos' => $totalProyectos,
                    'proyectos_por_estado' => $proyectosPorEstado,
                    'proyectos_por_fase' => $proyectosPorFase,
                    'data' => $proyectosTimeline
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener analíticas: ' . $e->getMessage()
            ], 500);
        }
    }

}