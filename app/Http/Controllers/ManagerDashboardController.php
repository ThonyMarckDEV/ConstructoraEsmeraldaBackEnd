<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\User;
use App\Models\Fase;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ManagerDashboardController extends Controller
{
    public function getAnalytics($idEncargado)
    {
        try {
            // Validate the encargado exists
            $encargado = User::findOrFail($idEncargado);
            
            // Get current date for delay calculations
            $today = Carbon::today();
            
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
            
            // Calculate delayed projects (end date has passed but still in progress)
            $proyectosRetrasados = $proyectos
                ->where('estado', 'En Progreso')
                ->filter(function($proyecto) use ($today) {
                    return Carbon::parse($proyecto->fecha_fin_estimada)->lt($today);
                });
            
            $totalProyectosRetrasados = $proyectosRetrasados->count();
            $porcentajeRetrasados = $totalProyectos > 0 ? round(($totalProyectosRetrasados / $totalProyectos) * 100, 1) : 0;
            
            // Calculate phase completion rates and average time per phase
            $fases = Fase::whereIn('idProyecto', $proyectos->pluck('idProyecto'))->get();
            $fasesTiempoPromedio = [];
            $fasesPorcentajeCompletado = [];
            
            $nombresFases = [
                'Planificación',
                'Preparación del Terreno',
                'Construcción de Cimientos',
                'Estructura y Superestructura',
                'Instalaciones',
                'Acabados',
                'Inspección y Pruebas',
                'Entrega'
            ];
            
            foreach ($nombresFases as $nombreFase) {
                $fasesDeEsteTipo = $fases->where('nombreFase', $nombreFase);
                $fasesCompletadas = $fasesDeEsteTipo->filter(function($fase) {
                    return !empty($fase->fecha_fin);
                });
                
                // Calculate completion percentage
                $totalFases = $fasesDeEsteTipo->count();
                $completadas = $fasesCompletadas->count();
                $porcentaje = $totalFases > 0 ? round(($completadas / $totalFases) * 100, 1) : 0;
                $fasesPorcentajeCompletado[$nombreFase] = $porcentaje;
                
                // Calculate average time for completed phases (in days)
                $tiempoTotal = 0;
                $contadorTiempo = 0;
                
                foreach ($fasesCompletadas as $fase) {
                    if (!empty($fase->fecha_inicio) && !empty($fase->fecha_fin)) {
                        $inicio = Carbon::parse($fase->fecha_inicio);
                        $fin = Carbon::parse($fase->fecha_fin);
                        $tiempoTotal += $inicio->diffInDays($fin);
                        $contadorTiempo++;
                    }
                }
                
                $tiempoPromedio = $contadorTiempo > 0 ? round($tiempoTotal / $contadorTiempo, 1) : 0;
                $fasesTiempoPromedio[$nombreFase] = $tiempoPromedio;
            }
            
            // Get projects with phases that are delayed
            $proyectosConFasesRetrasadas = [];
            foreach ($proyectos as $proyecto) {
                $fasesProyecto = $fases->where('idProyecto', $proyecto->idProyecto);
                $fasesRetrasadas = $fasesProyecto->filter(function($fase) use ($today) {
                    return !empty($fase->fecha_fin) && Carbon::parse($fase->fecha_fin)->lt($today) && empty($fase->fecha_fin);
                });
                
                if ($fasesRetrasadas->count() > 0) {
                    $proyectosConFasesRetrasadas[] = [
                        'id' => $proyecto->idProyecto,
                        'nombre' => $proyecto->nombre,
                        'fases_retrasadas' => $fasesRetrasadas->count()
                    ];
                }
            }
            
            // Prepare timeline data including project names, start dates, and durations
            $proyectosTimeline = $proyectos->map(function($proyecto) use ($today) {
                $startDate = new \DateTime($proyecto->fecha_inicio);
                $endDate = new \DateTime($proyecto->fecha_fin_estimada);
                $duration = $startDate->diff($endDate)->days / 30; // Converting to months
                
                // Calculate time remaining (in days)
                $diasRestantes = $today->diffInDays(Carbon::parse($proyecto->fecha_fin_estimada), false);
                
                // Calculate progress percentage based on dates
                $totalDias = Carbon::parse($proyecto->fecha_inicio)->diffInDays(Carbon::parse($proyecto->fecha_fin_estimada));
                $diasTranscurridos = Carbon::parse($proyecto->fecha_inicio)->diffInDays($today);
                $progresoTiempo = $totalDias > 0 ? min(100, round(($diasTranscurridos / $totalDias) * 100, 1)) : 0;
                
                return [
                    'id' => $proyecto->idProyecto,
                    'nombre' => $proyecto->nombre,
                    'fecha_inicio' => $proyecto->fecha_inicio,
                    'fecha_fin_estimada' => $proyecto->fecha_fin_estimada,
                    'estado' => $proyecto->estado,
                    'fase' => $proyecto->fase,
                    'duration' => round($duration, 1), // Round to 1 decimal place
                    'dias_restantes' => $diasRestantes,
                    'retrasado' => $diasRestantes < 0 && $proyecto->estado === 'En Progreso',
                    'progreso_tiempo' => $progresoTiempo
                ];
            });
            
            // Group projects by completion month (for monthly completion trend)
            $proyectosPorMes = [];
            foreach ($proyectos->where('estado', 'Finalizado') as $proyecto) {
                $fecha = Carbon::parse($proyecto->updated_at);
                $mes = $fecha->format('Y-m');
                
                if (!isset($proyectosPorMes[$mes])) {
                    $proyectosPorMes[$mes] = 0;
                }
                
                $proyectosPorMes[$mes]++;
            }
            
            // Sort by month
            ksort($proyectosPorMes);
            
            // Convert to format for charts
            $tendenciaFinalizacion = [];
            foreach ($proyectosPorMes as $mes => $cantidad) {
                $tendenciaFinalizacion[] = [
                    'month' => $mes,
                    'count' => $cantidad
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_proyectos' => $totalProyectos,
                    'proyectos_por_estado' => $proyectosPorEstado,
                    'proyectos_por_fase' => $proyectosPorFase,
                    'proyectos_retrasados' => [
                        'count' => $totalProyectosRetrasados,
                        'percentage' => $porcentajeRetrasados
                    ],
                    'fases_porcentaje_completado' => $fasesPorcentajeCompletado,
                    'fases_tiempo_promedio' => $fasesTiempoPromedio,
                    'proyectos_con_fases_retrasadas' => $proyectosConFasesRetrasadas,
                    'tendencia_finalizacion' => $tendenciaFinalizacion,
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