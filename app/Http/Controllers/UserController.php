<?php

namespace App\Http\Controllers;

use App\Models\Archivo;
use App\Models\Fase;
use App\Models\Foto;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function getProjects()
    {
        $user = auth()->user();
        $proyectos = $user->proyectos()
                   ->select('idProyecto', 'nombre','fecha_inicio','fecha_fin_estimada','fase')
                   ->get();
    
        return response()->json($proyectos);
    }

    /**
     * Get a specific project by ID
     */
    public function getProject($id)
    {
        $clientId = Auth::id();
        $project = Proyecto::where('idProyecto', $id)
                           ->where('idCliente', $clientId)
                           ->first();
        
        if (!$project) {
            return response()->json(['message' => 'Proyecto no encontrado'], 404);
        }
        
        return response()->json($project);
    }
    
    /**
     * Get phases for a specific project
     */
    public function getProjectPhases($id)
    {
        $clientId = Auth::id();
        $project = Proyecto::where('idProyecto', $id)
                           ->where('idCliente', $clientId)
                           ->first();
        
        if (!$project) {
            return response()->json(['message' => 'Proyecto no encontrado'], 404);
        }
        
        $phases = Fase::where('idProyecto', $id)->orderBy('idFase', 'asc')->get();
        
        return response()->json($phases);
    }
    
    /**
     * Get phases with files and photos for a specific project
     */
    public function getProjectDetails($id)
    {
        $clientId = Auth::id();
        $project = Proyecto::where('idProyecto', $id)
                        ->where('idCliente', $clientId)
                        ->first();
        
        if (!$project) {
            return response()->json(['message' => 'Proyecto no encontrado'], 404);
        }
        
        // Get phases for this project
        $phases = Fase::where('idProyecto', $id)
                    ->orderBy('idFase', 'asc')
                    ->get();

        // Structure the response
        $response = [
            'fase_actual' => $project->fase, // Nombre de la fase actual del proyecto
            'fases' => $phases->map(function ($phase) use ($project) {
                // Get files for this phase
                $files = Archivo::where('idFase', $phase->idFase)
                            ->get()
                            ->map(function ($file) {
                                return [
                                    'idArchivo' => $file->idArchivo,
                                    'ruta' => $file->ruta,
                                    'tipo' => $file->tipo,
                                    'descripcion' => $file->descripcion
                                ];
                            });
                
                // Get photos for this phase
                $photos = Foto::where('idFase', $phase->idFase)
                            ->get()
                            ->map(function ($photo) {
                                return [
                                    'idFoto' => $photo->idFoto,
                                    'ruta' => $photo->rutaFoto,
                                    'tipo' => $photo->tipo,
                                    'descripcion' => $photo->descripcion
                                ];
                            });
                
                return [
                    'idFase' => $phase->idFase,
                    'nombreFase' => $phase->nombreFase,
                    'descripcion' => $phase->descripcion,
                    'es_actual' => $phase->nombreFase === $project->fase, // Indica si es la fase actual
                    'archivos' => $files,
                    'fotos' => $photos
                ];
            })
        ];
        
        return response()->json($response);
    }

}