<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BomEstilo;
use App\Models\Estilo;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class BomEstiloController extends Controller
{
    /**
     * Obtener BOM de un estilo específico
     */
    public function porEstilo(string $estiloId): JsonResponse
    {
        try {
            $estilo = Estilo::findOrFail($estiloId);

            $bomItems = BomEstilo::porEstilo($estiloId)
                ->with([
                    'material.categoria',
                    'material.unidadMedida',
                    'proceso'
                ])
                ->activos()
                ->get();

            // Obtener estadísticas del BOM
            $estadisticas = BomEstilo::getEstadisticasPorEstilo($estiloId);

            // Transformar datos para incluir cálculos
            $bomDetallado = $bomItems->map(function ($item) {
                return $item->getInfoCompleta();
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'estilo' => $estilo,
                    'bom_items' => $bomDetallado,
                    'estadisticas' => $estadisticas,
                    'resumen' => [
                        'total_items' => $bomItems->count(),
                        'costo_total_materiales' => $estadisticas['costo_total_materiales'],
                        'items_criticos' => $estadisticas['items_criticos']
                    ]
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Estilo no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener BOM del estilo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar BOM completo de un estilo
     */
    public function actualizarBom(Request $request, string $estiloId): JsonResponse
    {
        try {
            $estilo = Estilo::findOrFail($estiloId);

            $validated = $request->validate([
                'items' => 'required|array',
                'items.*.id_material' => 'required|exists:materiales,id',
                'items.*.cantidad_base' => 'required|numeric|min:0',
                'items.*.id_proceso' => 'nullable|exists:procesos,id',
                'items.*.aplica_talla' => 'boolean',
                'items.*.aplica_color' => 'boolean',
                'items.*.es_critico' => 'boolean'
            ]);

            // Eliminar items existentes del BOM
            BomEstilo::where('id_estilo', $estiloId)->delete();

            // Crear nuevos items
            $itemsCreados = [];
            foreach ($validated['items'] as $itemData) {
                $itemData['id_estilo'] = $estiloId;
                $itemData['aplica_talla'] = $itemData['aplica_talla'] ?? true;
                $itemData['aplica_color'] = $itemData['aplica_color'] ?? false;
                $itemData['es_critico'] = $itemData['es_critico'] ?? false;

                $bomItem = BomEstilo::create($itemData);
                $itemsCreados[] = $bomItem->load(['material.categoria', 'material.unidadMedida', 'proceso']);
            }

            // Obtener estadísticas actualizadas
            $estadisticas = BomEstilo::getEstadisticasPorEstilo($estiloId);

            return response()->json([
                'success' => true,
                'message' => 'BOM actualizado exitosamente',
                'data' => [
                    'items_creados' => count($itemsCreados),
                    'bom_items' => $itemsCreados,
                    'estadisticas' => $estadisticas
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Estilo no encontrado'
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
                'message' => 'Error al actualizar BOM',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcular BOM para una variante específica (talla y color)
     */
    public function calcularPorVariante(Request $request, string $estiloId): JsonResponse
    {
        try {
            $estilo = Estilo::findOrFail($estiloId);

            $validated = $request->validate([
                'id_talla' => 'required|exists:tallas,id',
                'id_color' => 'nullable|exists:colores,id',
                'cantidad_piezas' => 'required|integer|min:1'
            ]);

            $bomItems = BomEstilo::porEstilo($estiloId)
                ->with([
                    'material.categoria',
                    'material.unidadMedida',
                    'proceso'
                ])
                ->activos()
                ->get();

            if ($bomItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El estilo no tiene BOM definido'
                ], 404);
            }

            // Obtener multiplicador de talla
            $talla = \App\Models\Talla::findOrFail($validated['id_talla']);
            $multiplicadorTalla = $talla->multiplicador_cantidad;

            // Calcular BOM para la variante
            $bomCalculado = $bomItems->map(function ($item) use ($multiplicadorTalla, $validated) {
                $infoCompleta = $item->getInfoCompleta($multiplicadorTalla, $validated['id_color']);

                // Calcular para la cantidad de piezas solicitada
                $cantidadTotalRequerida = $infoCompleta['bom_item']['cantidad_final'] * $validated['cantidad_piezas'];
                $costoTotalRequerido = $infoCompleta['costos']['costo_total'] * $validated['cantidad_piezas'];

                $infoCompleta['calculo_produccion'] = [
                    'cantidad_piezas' => $validated['cantidad_piezas'],
                    'cantidad_total_requerida' => $cantidadTotalRequerida,
                    'costo_total_requerido' => $costoTotalRequerido,
                    'stock_suficiente' => $item->material->stock_actual >= $cantidadTotalRequerida
                ];

                return $infoCompleta;
            });

            // Calcular totales
            $costoTotalMateriales = $bomCalculado->sum('calculo_produccion.costo_total_requerido');
            $itemsConStockInsuficiente = $bomCalculado->where('calculo_produccion.stock_suficiente', false)->count();

            // Verificar disponibilidad total
            $stockSuficiente = $itemsConStockInsuficiente === 0;

            $resumen = [
                'variante' => [
                    'estilo_id' => $estiloId,
                    'estilo_nombre' => $estilo->nombre,
                    'talla_id' => $validated['id_talla'],
                    'talla_nombre' => $talla->nombre,
                    'multiplicador_talla' => $multiplicadorTalla,
                    'color_id' => $validated['id_color'],
                    'cantidad_piezas' => $validated['cantidad_piezas']
                ],
                'costos' => [
                    'total_materiales' => $costoTotalMateriales,
                    'costo_por_pieza' => $costoTotalMateriales / $validated['cantidad_piezas']
                ],
                'disponibilidad' => [
                    'stock_suficiente' => $stockSuficiente,
                    'items_sin_stock' => $itemsConStockInsuficiente,
                    'total_items' => $bomCalculado->count()
                ],
                'alertas' => $bomCalculado->flatMap(function ($item) {
                    return $item['alertas'] ?? [];
                })->toArray()
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'bom_calculado' => $bomCalculado,
                    'resumen' => $resumen
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Estilo o talla no encontrada'
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
                'message' => 'Error al calcular BOM por variante',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Agregar item al BOM
     */
    public function agregarItem(Request $request, string $estiloId): JsonResponse
    {
        try {
            $estilo = Estilo::findOrFail($estiloId);

            $validated = $request->validate([
                'id_material' => 'required|exists:materiales,id',
                'cantidad_base' => 'required|numeric|min:0',
                'id_proceso' => 'nullable|exists:procesos,id',
                'aplica_talla' => 'boolean',
                'aplica_color' => 'boolean',
                'es_critico' => 'boolean'
            ]);

            // Verificar que no exista ya este material en el BOM
            $existeItem = BomEstilo::where('id_estilo', $estiloId)
                ->where('id_material', $validated['id_material'])
                ->exists();

            if ($existeItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este material ya existe en el BOM del estilo'
                ], 400);
            }

            $validated['id_estilo'] = $estiloId;
            $validated['aplica_talla'] = $validated['aplica_talla'] ?? true;
            $validated['aplica_color'] = $validated['aplica_color'] ?? false;
            $validated['es_critico'] = $validated['es_critico'] ?? false;

            $bomItem = BomEstilo::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Item agregado al BOM exitosamente',
                'data' => $bomItem->load(['material.categoria', 'material.unidadMedida', 'proceso'])
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Estilo no encontrado'
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
                'message' => 'Error al agregar item al BOM',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar item del BOM
     */
    public function eliminarItem(string $bomItemId): JsonResponse
    {
        try {
            $bomItem = BomEstilo::findOrFail($bomItemId);
            $bomItem->delete();

            return response()->json([
                'success' => true,
                'message' => 'Item eliminado del BOM exitosamente'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Item del BOM no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar item del BOM',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener reporte de BOM
     */
    public function getReporte(string $estiloId, Request $request): JsonResponse
    {
        try {
            $estilo = Estilo::findOrFail($estiloId);

            $validated = $request->validate([
                'id_talla' => 'nullable|exists:tallas,id',
                'id_color' => 'nullable|exists:colores,id'
            ]);

            $multiplicadorTalla = 1.0;
            if ($validated['id_talla']) {
                $talla = \App\Models\Talla::findOrFail($validated['id_talla']);
                $multiplicadorTalla = $talla->multiplicador_cantidad;
            }

            $bomItems = BomEstilo::porEstilo($estiloId)
                ->with([
                    'material.categoria',
                    'material.unidadMedida',
                    'proceso'
                ])
                ->activos()
                ->get();

            $reporte = $bomItems->map(function ($item) use ($multiplicadorTalla, $validated) {
                return $item->getResumenReporte($multiplicadorTalla, $validated['id_color']);
            });

            $estadisticas = BomEstilo::getEstadisticasPorEstilo($estiloId);

            return response()->json([
                'success' => true,
                'data' => [
                    'estilo' => $estilo,
                    'parametros' => $validated,
                    'multiplicador_talla' => $multiplicadorTalla,
                    'reporte_items' => $reporte,
                    'estadisticas' => $estadisticas,
                    'fecha_generacion' => now()->toISOString()
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Estilo no encontrado'
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
                'message' => 'Error al generar reporte de BOM',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
