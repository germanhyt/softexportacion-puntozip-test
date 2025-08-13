<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Talla;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class TallaController extends Controller
{
    /**
     * Listar tallas
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Talla::query();

            // Aplicar filtros
            if ($request->has('estado') && $request->estado !== 'todas') {
                if ($request->estado === 'activas') {
                    $query->activas();
                } else {
                    $query->inactivas();
                }
            }

            // Ordenamiento por defecto por orden
            $query->porOrden();

            $tallas = $query->get();

            return response()->json([
                'success' => true,
                'data' => $tallas
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las tallas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nueva talla
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'codigo' => 'required|string|max:10|unique:tallas,codigo',
                'nombre' => 'required|string|max:50',
                'multiplicador_cantidad' => 'required|numeric|min:0.1|max:10',
                'orden' => 'required|integer|min:1',
                'estado' => 'nullable|in:activo,inactivo'
            ]);

            $validated['estado'] = $validated['estado'] ?? 'activo';

            $talla = Talla::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Talla creada exitosamente',
                'data' => $talla
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la talla',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar talla específica
     */
    public function show(string $id): JsonResponse
    {
        try {
            $talla = Talla::with('variantes.estilo')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $talla
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Talla no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la talla',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar talla
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $talla = Talla::findOrFail($id);

            $validated = $request->validate([
                'codigo' => 'sometimes|string|max:10|unique:tallas,codigo,' . $id,
                'nombre' => 'sometimes|string|max:50',
                'multiplicador_cantidad' => 'sometimes|numeric|min:0.1|max:10',
                'orden' => 'sometimes|integer|min:1',
                'estado' => 'sometimes|in:activo,inactivo'
            ]);

            $talla->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Talla actualizada exitosamente',
                'data' => $talla
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Talla no encontrada'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la talla',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar talla
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $talla = Talla::findOrFail($id);

            // Verificar si tiene variantes asociadas
            if ($talla->variantes()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar la talla porque tiene variantes asociadas'
                ], 400);
            }

            $talla->update(['estado' => 'inactivo']);

            return response()->json([
                'success' => true,
                'message' => 'Talla eliminada exitosamente'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Talla no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la talla',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener tallas disponibles para dropdown
     */
    public function getTallasDisponibles(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => Talla::getTallasDisponibles()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tallas disponibles',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
