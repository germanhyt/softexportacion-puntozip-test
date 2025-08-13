<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Color;

class ColorController
{
    /**
     * Listar todos los colores disponibles
     */
    public function index(): JsonResponse
    {
        try {
            $colores = Color::where('estado', 'activo')
                          ->orderBy('nombre')
                          ->get();

            return response()->json([
                'success' => true,
                'data' => $colores
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener colores',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nuevo color
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'nombre' => 'required|string|max:50|unique:colores,nombre',
                'codigo_hex' => 'required|string|regex:/^#[A-Fa-f0-9]{6}$/|unique:colores,codigo_hex',
                'codigo_pantone' => 'nullable|string|max:20',
                'descripcion' => 'nullable|string|max:200'
            ]);

            $color = Color::create([
                'nombre' => $request->nombre,
                'codigo_hex' => $request->codigo_hex,
                'codigo_pantone' => $request->codigo_pantone,
                'descripcion' => $request->descripcion,
                'estado' => 'activo'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Color creado exitosamente',
                'data' => $color
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear color',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener color específico
     */
    public function show(string $id): JsonResponse
    {
        try {
            $color = Color::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $color
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Color no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener color',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
