<?php

namespace App\Http\Controllers;

use App\Models\Archivo;
use App\Models\Fase;
use App\Models\Foto;
use App\Models\Log;
use App\Models\Proyecto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
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
        

           // 2. Obtén el ID del usuario autenticado
            $usuarioId = Auth::id();
            
            // 3. Crea el registro en la tabla de logs
            Log::create([
                'id_Usuario' => $usuarioId,
                'registro' => 'Obtuvo los proyectos del cliente con sus fases'
            ]);

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

        // 2. Obtén el ID del usuario autenticado
        $usuarioId = Auth::id();
        
        // 3. Crea el registro en la tabla de logs
        Log::create([
            'id_Usuario' => $usuarioId,
            'registro' => 'Obtuvo el detalle de un proyecto del cliente con sus fases'
        ]);
    
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
                    'es_actual' => $phase->nombreFase === $project->fase,
                    'archivos' => $files,
                    'fotos' => $photos,
                    'modelo' => $phase->modelo
                ];
            })
        ];
        
        return response()->json($response);
    }

   public function download(Request $request, $path)
    {
        // **Validación básica de seguridad (MUY IMPORTANTE)**
        // Asegúrate que la URL pertenezca a tu bucket de MinIO para evitar que usen tu API como proxy abierto.
        // Reemplaza 'TU_DOMINIO_MINIO' con el dominio real de tu servidor MinIO.
        $minioBaseUrl = 'constructoraesmeralda-minio-server.thonymarckdev.online'; // SOLO el dominio
        if (!str_contains($path, $minioBaseUrl)) {
             Log::warning("Intento de descarga de URL no permitida: " . $path);
             abort(403, 'Acceso denegado al archivo.');
        }
        // Validar que realmente sea una URL
        if (!filter_var($path, FILTER_VALIDATE_URL)) {
            Log::warning("Intento de descarga con path inválido (no URL): " . $path);
            abort(400, 'Ruta de archivo inválida.');
        }


        try {
            // 1. Realiza la petición GET a la URL de MinIO
            $response = Http::timeout(60)->get($path); // Aumenta el timeout si son archivos grandes

            // 2. Verifica si la descarga desde MinIO fue exitosa
            if ($response->successful()) {
                $fileContent = $response->body(); // Obtiene el contenido del archivo
                $contentType = $response->header('Content-Type') ?: 'application/octet-stream'; // Obtiene el tipo de contenido original o usa uno genérico
                
                // Extrae el nombre del archivo de la URL
                $fileName = basename(parse_url($path, PHP_URL_PATH)); 

                // 3. Prepara las cabeceras para forzar la descarga en el navegador del usuario
                $headers = [
                    'Content-Type' => $contentType,
                    'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                    // Podrías añadir Content-Length si lo necesitas, aunque no es estrictamente necesario para streamDownload
                    // 'Content-Length' => strlen($fileContent) 
                ];

                // 4. Devuelve el contenido del archivo con las cabeceras de descarga
                // Opción A: Devolver directamente (funciona bien para archivos no gigantes)
                return response($fileContent, 200, $headers);

            } else {
                // Si MinIO devolvió un error (ej. 404 Not Found, 403 Forbidden)
                Log::error("Error al descargar desde MinIO: URL=" . $path . " Status=" . $response->status());
                abort(404, 'Archivo no encontrado en el servidor de almacenamiento o acceso denegado.');
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
             // Error si no se puede conectar a MinIO
             Log::error("Error de conexión al descargar desde MinIO: URL=" . $path . " Error=" . $e->getMessage());
             abort(503, 'No se pudo conectar al servidor de archivos.'); // Service Unavailable
        } catch (\Exception $e) {
            // Cualquier otro error inesperado
            Log::error("Error inesperado en la descarga: URL=" . $path . " Error=" . $e->getMessage());
            abort(500, 'Ocurrió un error interno al intentar descargar el archivo.');
        }
    }
}