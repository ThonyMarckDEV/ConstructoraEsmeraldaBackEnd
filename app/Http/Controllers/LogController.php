<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LogController extends Controller
{
    /**
     * Almacena un nuevo registro de log en la base de datos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validación de los datos de entrada
        $validator = Validator::make($request->all(), [
            'id_Usuario' => 'required|integer|exists:usuarios,idUsuario',
            'registro' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Crear el log si la validación es exitosa
        $log = Log::create([
            'id_Usuario' => $request->id_Usuario,
            'registro' => $request->registro,
        ]);

        // Retornar una respuesta exitosa
        return response()->json([
            'message' => 'Log registrado exitosamente',
            'data' => $log
        ], 201);
    }
}