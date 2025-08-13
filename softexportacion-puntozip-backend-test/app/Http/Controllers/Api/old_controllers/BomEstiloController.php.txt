<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\BomEstilo;
use App\Models\Estilo;

class BomEstiloController
{
    /**
     * Obtener BOM (Bill of Materials) por estilo
     */
    public function porEstilo(string $estiloId): JsonResponse
    {
        try {
            $estilo = Estilo::findOrFail($estiloId);
            
            $bomItems = BomEstilo::with(['material.categoria', 'material.unidadMedida', 'proceso'])
                                ->where('id_estilo', $estiloId)
                                ->where('estado', 'activo')
                                ->get();

            // Formatear items según la estructura de la BD
            $bomFormatted = $bomItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'id_material' => $item->id_material,
                    'material' => [
                        'codigo' => $item->material->codigo,
                        'nombre' => $item->material->nombre,
                        'categoria' => [
                            'nombre' => $item->material->categoria->nombre ?? 'Sin categoría'
                        ],
                        'unidad_medida' => [
                            'nombre' => $item->material->unidadMedida->nombre ?? 'Unidades',
                            'codigo' => $item->material->unidadMedida->codigo ?? 'ud'
                        ],
                        'costo_unitario' => (float) $item->material->costo_unitario
                    ],
                    'cantidad_base' => (float) $item->cantidad_base,
                    'es_critico' => (bool) $item->es_critico,
                    'proceso' => $item->proceso ? [
                        'nombre' => $item->proceso->nombre
                    ] : null
                ];
            });

            $resumen = [
                'total_items' => $bomItems->count(),
                'costo_total_materiales' => $bomItems->sum(function ($item) {
                    return $item->cantidad_base * $item->material->costo_unitario;
                }),
                'items_criticos' => $bomItems->where('es_critico', true)->count()
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'estilo' => $estilo->only(['id', 'nombre', 'codigo']),
                    'bom_items' => $bomFormatted,
                    'resumen' => $resumen
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
                'message' => 'Error al obtener BOM',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar BOM completo para un estilo
     */
    public function actualizarBom(Request $request, string $estiloId): JsonResponse
    {
        try {
            $request->validate([
                'items' => 'required|array',
                'items.*.id_material' => 'required|exists:materiales,id',
                'items.*.cantidad_base' => 'required|numeric|min:0',
                'items.*.id_proceso' => 'nullable|exists:procesos,id',
                'items.*.es_critico' => 'boolean'
            ]);

            $estilo = Estilo::findOrFail($estiloId);

            // Eliminar BOM existente
            BomEstilo::where('id_estilo', $estiloId)->delete();

            // Crear nuevos items de BOM
            foreach ($request->items as $itemData) {
                BomEstilo::create([
                    'id_estilo' => $estiloId,
                    'id_material' => $itemData['id_material'],
                    'cantidad_base' => $itemData['cantidad_base'],
                    'id_proceso' => $itemData['id_proceso'] ?? null,
                    'es_critico' => $itemData['es_critico'] ?? false,
                    'estado' => 'activo'
                ]);
            }

            // Recalcular y devolver BOM actualizado
            return $this->porEstilo($estiloId);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Estilo no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar BOM',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcular costo de BOM por variante (color/talla)
     */
    public function calcularPorVariante(Request $request, string $estiloId): JsonResponse
    {
        try {
            $request->validate([
                'color' => 'nullable|string',
                'talla' => 'required|string|in:XS,S,M,L,XL,XXL',
                'cantidad_piezas' => 'required|integer|min:1'
            ]);

            $bomItems = BomEstilo::with(['material'])
                                ->where('id_estilo', $estiloId)
                                ->get();

            // Multiplicadores por talla
            $tallaMultipliers = [
                'XS' => 0.90,
                'S' => 0.95,
                'M' => 1.00,
                'L' => 1.05,
                'XL' => 1.10,
                'XXL' => 1.15
            ];

            $multiplier = $tallaMultipliers[$request->talla] ?? 1.0;
            $cantidadPiezas = $request->cantidad_piezas;

            $calculoDetallado = $bomItems->map(function ($item) use ($multiplier, $cantidadPiezas) {
                $cantidadAjustada = $item->cantidad_base * $multiplier;
                $cantidadConMerma = $cantidadAjustada * 1.02; // 2% merma estándar
                $cantidadTotal = $cantidadConMerma * $cantidadPiezas;
                $costoTotal = $cantidadTotal * $item->material->costo_base;

                return [
                    'material' => $item->material->nombre,
                    'cantidad_base' => $item->cantidad_base,
                    'cantidad_ajustada_talla' => round($cantidadAjustada, 4),
                    'cantidad_con_merma' => round($cantidadConMerma, 4),
                    'cantidad_total' => round($cantidadTotal, 2),
                    'costo_unitario' => $item->material->costo_base,
                    'costo_total' => round($costoTotal, 2),
                    'es_critico' => $item->es_critico
                ];
            });

            $costoTotalBom = $calculoDetallado->sum('costo_total');

            return response()->json([
                'success' => true,
                'data' => [
                    'estilo_id' => $estiloId,
                    'variante' => [
                        'color' => $request->color,
                        'talla' => $request->talla,
                        'cantidad_piezas' => $cantidadPiezas
                    ],
                    'calculo_detallado' => $calculoDetallado,
                    'resumen' => [
                        'costo_total_materiales' => round($costoTotalBom, 2),
                        'costo_por_pieza' => round($costoTotalBom / $cantidadPiezas, 2),
                        'multiplier_talla' => $multiplier
                    ]
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
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
}
