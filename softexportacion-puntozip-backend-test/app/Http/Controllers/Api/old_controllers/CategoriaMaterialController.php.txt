<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\CategoriaMaterial;

class CategoriaMaterialController
{
    /**
     * Listar todas las categorías de materiales activas
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $categorias = CategoriaMaterial::activos()
                ->orderBy('nombre')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $categorias
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las categorías',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear una nueva categoría
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'nombre' => 'required|string|max:100|unique:categorias_materiales,nombre',
                'descripcion' => 'nullable|string|max:255',
                'estado' => 'nullable|in:activo,inactivo'
            ]);

            $categoria = CategoriaMaterial::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'estado' => $request->estado ?? 'activo'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Categoría creada exitosamente',
                'data' => $categoria
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
                'message' => 'Error al crear la categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar una categoría específica
     */
    public function show(string $id): JsonResponse
    {
        try {
            $categoria = CategoriaMaterial::with('materiales')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $categoria
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar una categoría
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $categoria = CategoriaMaterial::findOrFail($id);

            $request->validate([
                'nombre' => 'required|string|max:100|unique:categorias_materiales,nombre,' . $id,
                'descripcion' => 'nullable|string|max:255',
                'estado' => 'nullable|in:activo,inactivo'
            ]);

            $categoria->update($request->only(['nombre', 'descripcion', 'estado']));

            return response()->json([
                'success' => true,
                'message' => 'Categoría actualizada exitosamente',
                'data' => $categoria
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar (marcar como inactivo) una categoría
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $categoria = CategoriaMaterial::findOrFail($id);

            // Verificar si tiene materiales asociados
            if ($categoria->materiales()->activos()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar la categoría porque tiene materiales asociados'
                ], 400);
            }

            $categoria->update(['estado' => 'inactivo']);

            return response()->json([
                'success' => true,
                'message' => 'Categoría eliminada exitosamente'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
