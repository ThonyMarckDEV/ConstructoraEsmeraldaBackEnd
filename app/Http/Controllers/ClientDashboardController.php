<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\User;
use App\Models\Fase;
use App\Models\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ClientDashboardController extends Controller
{
public function getAnalytics()
    {
        try {
            $client = Auth::user();
            if ($client->idRol !== 2) {
                return response()->json(['success' => false, 'message' => 'Usuario no autorizado'], 403);
            }

            $today = Carbon::today();
            $proyectos = Proyecto::where('idCliente', $client->idUsuario)->get();
            $totalProyectos = $proyectos->count();

            if ($totalProyectos === 0) {
                return $this->getEmptyAnalyticsData();
            }

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
                return Carbon::parse($p->fecha_fin_estimada)->lt($today);
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
                ['name' => 'En Progreso', 'value' => $proyectosEnProgreso->count()],
                ['name' => 'Finalizado', 'value' => $proyectosFinalizados->count()],
            ];

            // 3. Gráfico: Proyectos por Fase Actual (Bar)
            $faseActualChart = [];
            $countPorFase = $proyectosEnProgreso->countBy('fase');
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
                    ->firstWhere('nombreFase', $proyecto->fase);

                if ($faseActualData && !empty($faseActualData->fecha_fin) && Carbon::parse($faseActualData->fecha_fin)->lt($today)) {
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
            
            // 6. Tabla: Lista Principal de Proyectos
            $listaProyectos = $proyectos->map(function($p) use ($today, $fases, $phaseIndex, $totalFasesCount) {
                $progresoTiempo = 0;
                
                if ($p->estado === 'Finalizado') {
                    $progresoTiempo = 100;
                } else {
                    $faseActualData = $fases->where('idProyecto', $p->idProyecto)->firstWhere('nombreFase', $p->fase);
                    if ($faseActualData && !empty($faseActualData->fecha_inicio) && !empty($faseActualData->fecha_fin)) {
                        $inicio = Carbon::parse($faseActualData->fecha_inicio);
                        $finPactado = Carbon::parse($faseActualData->fecha_fin);
                        $totalDias = $inicio->diffInDays($finPactado);
                        $diasTranscurridos = $inicio->diffInDays($today);

                        if ($totalDias > 0) {
                            $progreso = ($diasTranscurridos / $totalDias) * 100;
                            $progresoTiempo = round(min(100, max(0, $progreso)), 1);
                        } elseif ($diasTranscurridos >= 0) {
                            $progresoTiempo = 100; // Fase de 0 días que ya pasó
                        }
                    }
                }
                
                $diasRestantes = $today->diffInDays(Carbon::parse($p->fecha_fin_estimada), false);

                return [
                    'id' => $p->idProyecto,
                    'nombre' => $p->nombre,
                    'fase' => $p->fase,
                    'estado' => $p->estado,
                    'dias_restantes' => $diasRestantes,
                    'retrasado' => $diasRestantes < 0 && $p->estado === 'En Progreso',
                    'progreso_tiempo' => $progresoTiempo // Progreso % de la fase actual
                ];
            });

            // 7. Gráfico: Radar de Salud General
            $progresoPromedioFase = $listaProyectos->avg('progreso_tiempo');
            $porcentajeProyectosATiempo = $totalProyectos > 0 ? round(($totalProyectos - $kpis['proyectos_retrasados']) / $totalProyectos * 100, 1) : 100;
            $totalEnProgreso = $kpis['proyectos_en_progreso'];
            $porcentajeFasesATiempo = $totalEnProgreso > 0 ? round($retrasoFasesEnTiempo / $totalEnProgreso * 100, 1) : 100;

            $radarData = [
                ['subject' => 'Progreso Promedio', 'value' => round($progresoPromedioFase, 1), 'fullMark' => 100],
                ['subject' => 'Proyectos a Tiempo', 'value' => $porcentajeProyectosATiempo, 'fullMark' => 100],
                ['subject' => 'Fases a Tiempo', 'value' => $porcentajeFasesATiempo, 'fullMark' => 100],
                ['subject' => 'Proy. Finalizados', 'value' => $totalProyectos > 0 ? round($kpis['proyectos_finalizados'] / $totalProyectos * 100, 1) : 0, 'fullMark' => 100],
            ];
            
            // Log
            Log::create(['id_Usuario' => Auth::id(), 'registro' => 'Vio las analiticas']);
            
            // Respuesta Final
            return response()->json([
                'success' => true,
                'data' => [
                    'kpis' => $kpis,
                    'lista_proyectos' => $listaProyectos,
                    'estado_proyectos_chart' => $estadoProyectosChart,
                    'fase_actual_chart' => $faseActualChart,
                    'retraso_fases_chart' => $retrasoFasesChart,
                    'lista_fases_retrasadas' => $listaFasesRetrasadas,
                    'radar_data' => $radarData,
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener analíticas: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Función helper para retornar data vacía
    private function getEmptyAnalyticsData()
    {
        $kpis = [
            'total_proyectos' => 0, 'proyectos_en_progreso' => 0,
            'proyectos_finalizados' => 0, 'proyectos_retrasados' => 0,
            'porcentaje_retrasados' => 0
        ];
        $radar = [
            ['subject' => 'Progreso Promedio', 'value' => 0, 'fullMark' => 100],
            ['subject' => 'Proyectos a Tiempo', 'value' => 100, 'fullMark' => 100],
            ['subject' => 'Fases a Tiempo', 'value' => 100, 'fullMark' => 100],
            ['subject' => 'Proy. Finalizados', 'value' => 0, 'fullMark' => 100],
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