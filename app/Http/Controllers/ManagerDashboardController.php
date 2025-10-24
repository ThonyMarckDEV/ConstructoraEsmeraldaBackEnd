<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\User;
use App\Models\Fase;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ManagerDashboardController extends Controller
{
public function getAnalytics($idEncargado) 
    {
        try {
            // Validar que el encargado existe y tiene el rol correcto (ej. idRol = 3 para encargado)
            $encargado = User::where('idUsuario', $idEncargado)->where('idRol', 3)->firstOrFail();
            
            $today = Carbon::today();
            
            // Proyectos asignados a ESTE encargado
            $proyectos = Proyecto::where('idEncargado', $idEncargado)->get();
            $totalProyectos = $proyectos->count();

            if ($totalProyectos === 0) {
                return $this->getEmptyAnalyticsData(); // Retorna datos vacíos si no hay proyectos
            }

            // Fases de los proyectos de este encargado
            $fases = Fase::whereIn('idProyecto', $proyectos->pluck('idProyecto'))->get();
            $nombresFases = [
                'Planificación', 'Preparación del Terreno', 'Construcción de Cimientos',
                'Estructura y Superestructura', 'Instalaciones', 'Acabados',
                'Inspección y Pruebas', 'Entrega'
            ];
            $phaseIndex = array_flip($nombresFases);
            $totalFasesCount = count($nombresFases);

            // 1. KPIs Principales
            $proyectosEnProgreso = $proyectos->where('estado', 'En Progreso');
            $proyectosFinalizados = $proyectos->where('estado', 'Finalizado');
            $proyectosRetrasadosNivelProyecto = $proyectosEnProgreso->filter(function($p) use ($today) {
                // Un proyecto está retrasado si su fecha fin estimada ya pasó y sigue en progreso
                return !empty($p->fecha_fin_estimada) && Carbon::parse($p->fecha_fin_estimada)->lt($today);
            });

            $kpis = [
                'total_proyectos' => $totalProyectos,
                'proyectos_en_progreso' => $proyectosEnProgreso->count(),
                'proyectos_finalizados' => $proyectosFinalizados->count(),
                'proyectos_retrasados' => $proyectosRetrasadosNivelProyecto->count(),
                'porcentaje_retrasados' => $totalProyectos > 0 ? round(($proyectosRetrasadosNivelProyecto->count() / $totalProyectos) * 100, 1) : 0
            ];

            // 2. Gráfico: Estado de Proyectos (Pie)
            $estadoProyectosChart = [
                ['name' => 'En Progreso', 'value' => $kpis['proyectos_en_progreso']],
                ['name' => 'Finalizado', 'value' => $kpis['proyectos_finalizados']],
            ];

            // 3. Gráfico: Proyectos por Fase Actual (Bar) - Solo cuenta proyectos EN PROGRESO
            $faseActualChart = [];
            $countPorFase = $proyectosEnProgreso->countBy('fase'); // Usa la columna 'fase' de la tabla 'proyectos'
            foreach ($nombresFases as $nombre) {
                if (isset($countPorFase[$nombre]) && $countPorFase[$nombre] > 0) {
                    $faseActualChart[] = ['name' => $nombre, 'count' => $countPorFase[$nombre]];
                }
            }
            
            // 4. Gráfico: Retraso de Fases Actuales (Pie) y 5. Tabla: Fases Retrasadas
            $retrasoFasesEnTiempo = 0;
            $retrasoFasesRetrasadas = 0;
            $listaFasesRetrasadas = [];
            
            foreach ($proyectosEnProgreso as $proyecto) {
                $faseActualData = $fases
                    ->where('idProyecto', $proyecto->idProyecto)
                    ->firstWhere('nombreFase', $proyecto->fase); // Busca la fase actual en la tabla 'fases'

                $isPhaseDelayed = false;
                // Una fase está retrasada si tiene fecha_fin pactada, esa fecha ya pasó, Y el proyecto sigue 'En Progreso'
                if ($faseActualData && !empty($faseActualData->fecha_fin) && Carbon::parse($faseActualData->fecha_fin)->lt($today)) {
                   $isPhaseDelayed = true;
                }

                if ($isPhaseDelayed) {
                    $retrasoFasesRetrasadas++;
                    $listaFasesRetrasadas[] = [
                        'id' => $proyecto->idProyecto,
                        'nombre' => $proyecto->nombre,
                        'fase_retrasada' => $faseActualData->nombreFase,
                        'dias_retraso' => $today->diffInDays(Carbon::parse($faseActualData->fecha_fin))
                    ];
                } else {
                    $retrasoFasesEnTiempo++;
                }
            }
            
            $retrasoFasesChart = [
                ['name' => 'En Tiempo', 'value' => $retrasoFasesEnTiempo],
                ['name' => 'Retrasados', 'value' => $retrasoFasesRetrasadas]
            ];
            
            // 6. Tabla: Lista Principal de Proyectos (para el frontend)
            $listaProyectos = $proyectos->map(function($p) use ($today, $fases) {
                $progresoTiempoFaseActual = 0; // Progreso % de la fase actual
                
                if ($p->estado === 'Finalizado') {
                    $progresoTiempoFaseActual = 100;
                } else {
                    $faseActualData = $fases->where('idProyecto', $p->idProyecto)->firstWhere('nombreFase', $p->fase);
                    if ($faseActualData && !empty($faseActualData->fecha_inicio) && !empty($faseActualData->fecha_fin)) {
                        $inicio = Carbon::parse($faseActualData->fecha_inicio);
                        $finPactado = Carbon::parse($faseActualData->fecha_fin);
                        $totalDias = max(1, $inicio->diffInDays($finPactado)); // Evitar división por cero, mínimo 1 día
                        $diasTranscurridos = $inicio->diffInDays($today);

                        $progreso = ($diasTranscurridos / $totalDias) * 100;
                        $progresoTiempoFaseActual = round(min(100, max(0, $progreso)), 1); // Asegurar entre 0 y 100
                        
                    }
                    // Si no hay fechas de inicio/fin para la fase actual, el progreso es 0
                }
                
                // Días restantes para la fecha fin *estimada* del *proyecto*
                $diasRestantesProyecto = !empty($p->fecha_fin_estimada) ? $today->diffInDays(Carbon::parse($p->fecha_fin_estimada), false) : null;
                // Determinar si el proyecto (no la fase) está retrasado
                $proyectoRetrasado = $p->estado === 'En Progreso' && $diasRestantesProyecto !== null && $diasRestantesProyecto < 0;

                return [
                    'id' => $p->idProyecto,
                    'nombre' => $p->nombre,
                    'fase' => $p->fase,        // Fase actual del proyecto
                    'estado' => $p->estado,    // Estado del proyecto
                    'dias_restantes' => $diasRestantesProyecto, // Días restantes del proyecto
                    'retrasado' => $proyectoRetrasado, // Si el proyecto está retrasado
                    'progreso_fase_actual' => $progresoTiempoFaseActual // Progreso % de la fase actual
                ];
            });

            // 7. Gráfico: Radar de Salud General (Simplificado)
            $progresoPromedioFase = $listaProyectos->where('estado', 'En Progreso')->avg('progreso_fase_actual');
            $porcentajeProyectosATiempo = $kpis['total_proyectos'] > 0 ? round(($kpis['total_proyectos'] - $kpis['proyectos_retrasados']) / $kpis['total_proyectos'] * 100, 1) : 100;
            $totalEnProgreso = $kpis['proyectos_en_progreso'];
            $porcentajeFasesATiempo = $totalEnProgreso > 0 ? round($retrasoFasesEnTiempo / $totalEnProgreso * 100, 1) : 100;

            $radarData = [
                ['subject' => 'Progreso Prom.', 'value' => round($progresoPromedioFase ?? 0, 1), 'fullMark' => 100],
                ['subject' => 'Proy. a Tiempo', 'value' => $porcentajeProyectosATiempo, 'fullMark' => 100],
                ['subject' => 'Fases a Tiempo', 'value' => $porcentajeFasesATiempo, 'fullMark' => 100],
                ['subject' => 'Proy. Finaliz.', 'value' => $totalProyectos > 0 ? round($kpis['proyectos_finalizados'] / $totalProyectos * 100, 1) : 0, 'fullMark' => 100],
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'kpis' => $kpis,
                    'lista_proyectos' => $listaProyectos, // Lista principal para la tabla
                    'estado_proyectos_chart' => $estadoProyectosChart, // Pie: Progreso vs Finalizado
                    'fase_actual_chart' => $faseActualChart, // Bar: Cuántos proyectos en cada fase
                    'retraso_fases_chart' => $retrasoFasesChart, // Pie: Fases actuales En Tiempo vs Retrasadas
                    'lista_fases_retrasadas' => $listaFasesRetrasadas, // Tabla: Proyectos con fase actual retrasada
                    'radar_data' => $radarData, // Radar: Salud general
                ]
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Captura específica si el encargado no se encuentra
            return response()->json([
                'success' => false,
                'message' => 'Encargado no encontrado.'
            ], 404);
        } catch (\Exception $e) {
            // Captura genérica para otros errores
            Log::error('Error en ManagerDashboardController@getAnalytics: ' . $e->getMessage()); // Loguea el error
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener analíticas: ' . $e->getMessage() // Devuelve un mensaje genérico o el error si es seguro
            ], 500);
        }
    }
    
    // Función helper para data vacía (cuando no hay proyectos)
    private function getEmptyAnalyticsData()
    {
        $kpis = [
            'total_proyectos' => 0, 'proyectos_en_progreso' => 0,
            'proyectos_finalizados' => 0, 'proyectos_retrasados' => 0,
            'porcentaje_retrasados' => 0
        ];
        $radar = [
            ['subject' => 'Progreso Prom.', 'value' => 0, 'fullMark' => 100],
            ['subject' => 'Proy. a Tiempo', 'value' => 100, 'fullMark' => 100],
            ['subject' => 'Fases a Tiempo', 'value' => 100, 'fullMark' => 100],
            ['subject' => 'Proy. Finaliz.', 'value' => 0, 'fullMark' => 100],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'kpis' => $kpis,
                'lista_proyectos' => [],
                'estado_proyectos_chart' => [['name' => 'Sin Proyectos', 'value' => 1]],
                'fase_actual_chart' => [],
                'retraso_fases_chart' => [['name' => 'Sin Proyectos', 'value' => 1]],
                'lista_fases_retrasadas' => [],
                'radar_data' => $radar,
            ]
        ]);
    }
}