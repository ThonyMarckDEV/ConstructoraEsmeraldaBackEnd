<?php

namespace App\Http\Controllers;

use App\Models\Archivo;
use App\Models\Fase;
use App\Models\Foto;
use App\Models\Proyecto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ClientProjectController extends Controller
{


    /**
     * Obtiene todos los proyectos de un cliente con sus fases y datos del encargado
     */
    public function getClientProjectsWithPhases()
    {
        $clienteId = Auth::id();
        
        // Get all projects for this client with manager information
        $projects = Proyecto::where('idCliente', $clienteId)
                        ->with(['encargado' => function($query) {
                            $query->select('idUsuario', 'idDatos', 'username');
                        }, 'encargado.datos' => function($query) {
                            $query->select('idDatos', 'nombre', 'apellido', 'email', 'telefono');
                        }])
                        ->get();
        
        // For each project, get its phases
        foreach ($projects as $project) {
            $project->fases = Fase::where('idProyecto', $project->idProyecto)
                                ->orderBy('idFase', 'asc')
                                ->get();
        }
        
        return response()->json($projects);
    }

    /**
     * Get a specific project with its phases by ID for a client
     */
    public function getProjectWithPhases($id)
    {
        $clientId = Auth::id();
        
        $project = Proyecto::where('idProyecto', $id)
                        ->where('idCliente', $clientId)
                        ->with(['encargado' => function($query) {
                            $query->select('idUsuario', 'idDatos', 'username');
                        }, 'encargado.datos' => function($query) {
                            $query->select('idDatos', 'nombre', 'apellido', 'email', 'telefono');
                        }])
                        ->first();
                
        if (!$project) {
            return response()->json(['message' => 'Proyecto no encontrado'], 404);
        }
        
        $phases = Fase::where('idProyecto', $id)
                    ->orderBy('idFase', 'asc')
                    ->get();
        
        // Return combined data in a single response
        return response()->json([
            'proyecto' => $project,
            'fases' => $phases
        ]);
    }

    /**
     * Get phases with files and photos for a specific project
     */
    public function getProjectDetails($id)
    {
        // This method works for both client and manager, just need to check auth
        $userId = Auth::id();
        
        // Find the project ensuring the current user is either client or manager
        $project = Proyecto::where('idProyecto', $id)
                        ->where(function($query) use ($userId) {
                            $query->where('idCliente', $userId)
                                ->orWhere('idEncargado', $userId);
                        })
                        ->with(['cliente.datos', 'encargado.datos'])
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
                                    'ruta' => $photo->ruta,
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

    public function download(Request $request, $path)
    {
        // Verifica que el archivo exista en el disco (por ejemplo, 'public')
        if (Storage::disk('public')->exists($path)) {
            // Retorna el archivo forzando la descarga
            return Storage::disk('public')->download($path);
        } else {
            abort(404, 'Archivo no encontrado');
        }
    }
}