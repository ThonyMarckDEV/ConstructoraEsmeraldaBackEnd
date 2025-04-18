<?php

namespace App\Http\Controllers;

use App\Models\Archivo;
use App\Models\Fase;
use App\Models\Foto;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ManagerController extends Controller
{

    /**
     * Get all projects with their phases for a manager, including client information
     */
    public function getManagerProjectsWithPhases()
    {
        $encargadoId = Auth::id();
        
        // Get all projects for this manager with client information
        $projects = Proyecto::where('idEncargado', $encargadoId)
                        ->with(['cliente' => function($query) {
                            $query->select('idUsuario', 'idDatos', 'username');
                        }, 'cliente.datos' => function($query) {
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
     * Get a specific project with its phases by ID for a manager
     */
    public function getProjectWithPhases($id)
    {
        $encargadoId = Auth::id();
        
        $project = Proyecto::where('idProyecto', $id)
                        ->where('idEncargado', $encargadoId)
                        ->with(['cliente' => function($query) {
                            $query->select('idUsuario', 'idDatos', 'username');
                        }, 'cliente.datos' => function($query) {
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
        $encargadoId = Auth::id();
        
        // Update to include datos table
        $project = Proyecto::where('idProyecto', $id)
                        ->where('idEncargado', $encargadoId)
                        ->with(['cliente.datos'])
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

     /**
     * Actualiza la fase de un proyecto
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id ID del proyecto
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePhase(Request $request, $id)
    {
        // Validar la entrada
        $validator = Validator::make($request->all(), [
            'fase' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Encontrar el proyecto
            $proyecto = Proyecto::find($id);
            
            if (!$proyecto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proyecto no encontrado'
                ], 404);
            }
            
            // Validar la fase seleccionada
            $fasesValidas = [
                'Planificación',
                'Preparación del Terreno',
                'Construcción de Cimientos',
                'Estructura y Superestructura',
                'Instalaciones',
                'Acabados',
                'Inspección y Pruebas',
                'Entrega'
            ];
            
            $nuevaFase = $request->fase;
            
            // Verificar si la fase está en la lista de fases válidas
            if (!in_array($nuevaFase, $fasesValidas)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La fase seleccionada no es válida'
                ], 422);
            }
            
          // Actualizar la fase del proyecto
            $proyecto->fase = $nuevaFase;
            $proyecto->save();

            // Registrar la actividad
            Log::info("Proyecto ID {$id} actualizado a fase: {$nuevaFase}");

            return response()->json([
                'success' => true,
                'message' => 'Fase del proyecto actualizada correctamente',
                'data' => $proyecto
            ], 200);

            } catch (\Exception $e) {
                Log::error("Error al actualizar fase del proyecto: " . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Error al actualizar la fase del proyecto',
                    'error' => $e->getMessage()
                ], 500);
            }
        }





      /**
     * Subir un archivo a una fase específica
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadFile(Request $request)
    {
        // Validación de datos
        $validator = Validator::make($request->all(), [
            'archivo' => 'required|file|mimes:pdf,xls,xlsx,doc,docx,dwg|max:20480', // 20MB max
            'idFase' => 'required|exists:fases,idFase',
            'idProyecto' => 'required|exists:proyectos,idProyecto',
            'descripcion' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar que la fase pertenece al proyecto
        $fase = Fase::where('idFase', $request->idFase)
            ->where('idProyecto', $request->idProyecto)
            ->first();

        if (!$fase) {
            return response()->json([
                'success' => false,
                'message' => 'La fase no pertenece al proyecto especificado'
            ], 404);
        }

        try {
            // Obtener el archivo
            $file = $request->file('archivo');
            
            // Determinar el tipo de archivo
            $fileExtension = $file->getClientOriginalExtension();
            $tipoArchivo = strtolower($fileExtension);
            
            // Validar que el tipo sea permitido
            $allowedTypes = ['pdf', 'xls', 'xlsx', 'doc', 'docx', 'dwg'];
            if (!in_array($tipoArchivo, $allowedTypes)) {
                $tipoArchivo = 'pdf'; // Tipo por defecto
            }
            
            // Crear el directorio si no existe
            $path = "proyectos/{$request->idProyecto}/fases/{$request->idFase}/archivos";
            
            // Generar un nombre único para el archivo
            $fileName = time() . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '', $file->getClientOriginalName());
            
            // Guardar el archivo en el sistema de archivos
            $filePath = $file->storeAs($path, $fileName, 'public');
            
            // Guardar los datos en la base de datos
            $archivo = new Archivo();
            $archivo->nombre = $file->getClientOriginalName();
            $archivo->idFase = $request->idFase;
            $archivo->tipo = $tipoArchivo;
            // $archivo->ruta = Storage::url($filePath);
            $archivo->ruta = $filePath;
            $archivo->descripcion = $request->descripcion ?? 'Archivo subido: ' . $file->getClientOriginalName();
            $archivo->created_at = now(); // Establecer la fecha de creación
            $archivo->updated_at = now(); // Establecer la fecha de actualización
            $archivo->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Archivo subido correctamente',
                'data' => $archivo
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir el archivo: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Subir una foto a una fase específica
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadPhoto(Request $request)
    {
        // Validación de datos
        $validator = Validator::make($request->all(), [
            'foto' => 'required|file|mimes:jpg,jpeg,png,avif,webp|max:5120', // 5MB max
            'idFase' => 'required|exists:fases,idFase',
            'idProyecto' => 'required|exists:proyectos,idProyecto',
            'descripcion' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar que la fase pertenece al proyecto
        $fase = Fase::where('idFase', $request->idFase)
            ->where('idProyecto', $request->idProyecto)
            ->first();

        if (!$fase) {
            return response()->json([
                'success' => false,
                'message' => 'La fase no pertenece al proyecto especificado'
            ], 404);
        }

        try {
            // Obtener la foto
            $photo = $request->file('foto');
            
            // Determinar el tipo de foto
            $fileExtension = $photo->getClientOriginalExtension();
            $tipoFoto = strtolower($fileExtension);
            
            // Validar que el tipo sea permitido
            $allowedTypes = ['jpg', 'jpeg', 'png', 'avif', 'webp'];
            if (!in_array($tipoFoto, $allowedTypes)) {
                $tipoFoto = 'jpg'; // Tipo por defecto
            }
            
            // Crear el directorio si no existe
            $path = "proyectos/{$request->idProyecto}/fases/{$request->idFase}/fotos";
            
            // Generar un nombre único para la foto
            $fileName = time() . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '', $photo->getClientOriginalName());
            
            // Guardar la foto en el sistema de archivos
            $filePath = $photo->storeAs($path, $fileName, 'public');

            // Guardar los datos en la base de datos
            $foto = new Foto();
            $foto->nombre = $photo->getClientOriginalName();
            $foto->idFase = $request->idFase;
            $foto->tipo = $tipoFoto;
           // $foto->ruta = Storage::url($filePath);
            $foto->ruta = $filePath;
            $foto->descripcion = $request->descripcion ?? 'Foto subida: ' . $photo->getClientOriginalName();
            $foto->created_at = now(); // Establecer la fecha de creación
            $foto->updated_at = now(); // Establecer la fecha de actualización
            $foto->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Foto subida correctamente',
                'data' => $foto
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir la foto: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteFile(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'type' => 'required|in:archivo,foto'
        ]);
    
        try {
            if ($request->type === 'archivo') {
                $file = Archivo::findOrFail($request->id);
                $path = $file->ruta;
                $file->delete();
            } else {
                $file = Foto::findOrFail($request->id);
                $path = $file->ruta;
                $file->delete();
            }
    
            // Eliminar el archivo físico
            // Usar el disco 'public' explícitamente
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
    
            return response()->json([
                'success' => true,
                'message' => 'Archivo eliminado correctamente'
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    

    public function obtenerModelo($idProyecto)
    {
        $proyecto = Proyecto::findOrFail($idProyecto);
        
        if (empty($proyecto->modelo)) {
            return response()->json([
                'success' => false,
                'message' => 'El proyecto no tiene modelo asociado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'modelo_path' => $proyecto->modelo,
                'modelo_url' => "https://constructora-esmeralda-backend.thonymarckdev.online/api/manager/project/{$idProyecto}/modelo-file" // Nueva URL directa
            ]
        ]);
    }

    public function descargarModelo($idProyecto)
    {
        $proyecto = Proyecto::findOrFail($idProyecto);
        $rutaModelo = storage_path('app/public/' . $proyecto->modelo);
    
        if (!file_exists($rutaModelo)) {
            abort(404, 'Archivo de modelo no encontrado');
        }
    
        $response = response()->file($rutaModelo, [
            'Content-Type' => 'model/gltf-binary',
        ]);
    
        // Agrega los headers CORS
        $response->headers->set('Access-Control-Allow-Origin', 'https://constructora-esmeralda-front-end.vercel.app');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Expose-Headers', 'Content-Disposition');
    
        return $response;
    }
    

    public function subirModelo(Request $request)
    {
        Log::info('REQUEST HEADERS', getallheaders());
        Log::info('Content-Type:', [$request->header('Content-Type')]);
        Log::info('Raw Content:', [$request->getContent()]);

        Log::info('File modelo:', [
            'hasFile' => $request->hasFile('modelo'),
            'file' => $request->file('modelo'),
            'isValid' => $request->hasFile('modelo') ? $request->file('modelo')->isValid() : false,
        ]);

        // Verificar si el archivo está presente en la solicitud
        if (!$request->hasFile('modelo') || !$request->file('modelo')->isValid()) {
            Log::error('Archivo no válido o no recibido.', [
                'hasFile' => $request->hasFile('modelo'),
                'file' => $request->file('modelo')
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Archivo no válido o no recibido.'
            ], 400);
        }


        // Validar la solicitud
        $validator = Validator::make($request->all(), [
            'idProyecto' => 'required|exists:proyectos,idProyecto',
            'modelo' => 'required|file', // 50MB max (51200KB)
        ]);

        if ($validator->fails()) {
            Log::error('Validación fallida.', ['errors' => $validator->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Buscar el proyecto
            $proyecto = Proyecto::findOrFail($request->idProyecto);
            Log::info('Proyecto encontrado:', ['proyecto' => $proyecto]);

            // Crear el directorio si no existe
            $path = "proyectos/{$request->idProyecto}/modelo";
            Log::info('Ruta del modelo:', ['path' => $path]);

            // Generar un nombre limpio para el archivo (minúsculas, sin caracteres especiales)
            $originalName = pathinfo($request->file('modelo')->getClientOriginalName(), PATHINFO_FILENAME);
            $cleanName = 'modelo_' . preg_replace('/[^a-z0-9]/', '', strtolower($originalName)) . '_' . time() . '.glb';
            Log::info('Nombre limpio generado:', ['cleanName' => $cleanName]);

            // Verificar si hay un modelo anterior para eliminarlo
            if ($proyecto->modelo && Storage::disk('public')->exists($proyecto->modelo)) {
                Log::info('Eliminando modelo anterior:', ['modelo' => $proyecto->modelo]);
                Storage::disk('public')->delete($proyecto->modelo);
            }

            // Guardar el modelo en el sistema de archivos
            $filePath = $request->file('modelo')->storeAs($path, $cleanName, 'public');
            Log::info('Modelo guardado en:', ['filePath' => $filePath]);

            // Actualizar la ruta del modelo en la base de datos
            $proyecto->modelo = $filePath;
            $proyecto->save();
            Log::info('Ruta del modelo guardada en la base de datos.', ['modelo' => $proyecto->modelo]);

            return response()->json([
                'success' => true,
                'message' => 'Modelo subido correctamente',
                'data' => [
                    'modelo_path' => Storage::url($filePath),
                    'proyecto_id' => $proyecto->idProyecto
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al subir el modelo.', [
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al subir el modelo',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    

}