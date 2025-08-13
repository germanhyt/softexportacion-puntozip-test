<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CalculoVariante;
use App\Models\VarianteEstilo;
use App\Models\Estilo;
use App\Models\Color;
use App\Models\Talla;
use App\Models\BomEstilo;
use App\Models\FlujoEstilo;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class CalculoVarianteController extends Controller
{
    /**
     * Calcular variante textil completa
     */
    public function calcularVariante(Request $request, string $estiloId): JsonResponse
    {
        try {
            $estilo = Estilo::with(['bomItems.material', 'flujoActual.nodos.proceso'])->findOrFail($estiloId);

            $validated = $request->validate([
                'id_color' => 'required|exists:colores,id',
                'id_talla' => 'required|exists:tallas,id',
                'cantidad_piezas' => 'required|integer|min:1',
                'id_flujo_estilo' => 'nullable|exists:flujos_estilos,id'
            ]);

            // Usar flujo actual del estilo si no se especifica uno
            $flujoId = $validated['id_flujo_estilo'] ?? $estilo->flujoActual?->id;
            
            if (!$flujoId) {
                return response()->json([
                    'success' => false,
                    'message' => 'El estilo no tiene un flujo de procesos definido'
                ], 400);
            }

            $flujo = FlujoEstilo::with(['nodos.proceso'])->findOrFail($flujoId);
            $color = Color::findOrFail($validated['id_color']);
            $talla = Talla::findOrFail($validated['id_talla']);

            DB::beginTransaction();

            try {
                // 1. Crear o encontrar variante del estilo
                $variante = VarianteEstilo::firstOrCreate([
                    'id_estilo' => $estiloId,
                    'id_color' => $validated['id_color'],
                    'id_talla' => $validated['id_talla']
                ], [
                    'codigo_sku' => $this->generarSKU($estilo, $color, $talla),
                    'estado' => 'activo'
                ]);

                // 2. Calcular BOM con multiplicador de talla
                $multiplicadorTalla = $talla->multiplicador_cantidad;
                $bomItems = $estilo->bomItems()->with('material')->get();
                
                $costoMateriales = 0;
                $bomCalculado = [];

                foreach ($bomItems as $bomItem) {
                    $cantidadFinal = $bomItem->calcularCantidadFinal($multiplicadorTalla);
                    $costoItem = $bomItem->calcularCostoTotal($multiplicadorTalla, $validated['id_color']);
                    
                    $costoMateriales += $costoItem * $validated['cantidad_piezas'];
                    
                    $bomCalculado[] = [
                        'material' => [
                            'id' => $bomItem->material->id,
                            'codigo' => $bomItem->material->codigo,
                            'nombre' => $bomItem->material->nombre,
                            'tipo' => $bomItem->material->tipo_material
                        ],
                        'cantidad_base' => $bomItem->cantidad_base,
                        'cantidad_final' => $cantidadFinal,
                        'cantidad_total_requerida' => $cantidadFinal * $validated['cantidad_piezas'],
                        'costo_unitario' => $bomItem->material->costo_unitario,
                        'costo_total' => $costoItem * $validated['cantidad_piezas'],
                        'aplica_talla' => $bomItem->aplica_talla,
                        'aplica_color' => $bomItem->aplica_color,
                        'es_critico' => $bomItem->es_critico
                    ];
                }

                // 3. Calcular procesos del flujo
                $costoProcesos = 0;
                $tiempoTotal = 0;
                $tiempoParalelo = 0;
                $procesosParalelos = [];
                $flujoCalculado = [];

                foreach ($flujo->nodos()->with('proceso')->orderBy('orden_secuencia')->get() as $nodo) {
                    $proceso = $nodo->proceso;
                    $costo = $nodo->costo_personalizado ?? $proceso->costo_base;
                    $tiempo = $nodo->tiempo_personalizado_min ?? $proceso->tiempo_base_min;

                    // Aplicar merma si existe
                    if ($proceso->merma_porcentaje > 0) {
                        $factorMerma = 1 + ($proceso->merma_porcentaje / 100);
                        $costo *= $factorMerma;
                        $tiempo *= $factorMerma;
                    }

                    $costoProcesos += $costo * $validated['cantidad_piezas'];

                    // Manejar tiempos de procesos paralelos
                    if ($proceso->es_paralelo) {
                        $procesosParalelos[] = $tiempo;
                    } else {
                        // Si hay procesos paralelos acumulados, tomar el máximo
                        if (!empty($procesosParalelos)) {
                            $tiempoTotal += max($procesosParalelos);
                            $procesosParalelos = [];
                        }
                        $tiempoTotal += $tiempo;
                    }

                    $flujoCalculado[] = [
                        'proceso' => [
                            'id' => $proceso->id,
                            'codigo' => $proceso->codigo,
                            'nombre' => $proceso->nombre,
                            'tipo' => $proceso->tipoProceso->nombre ?? 'Sin tipo'
                        ],
                        'orden_secuencia' => $nodo->orden_secuencia,
                        'costo_base' => $proceso->costo_base,
                        'costo_personalizado' => $nodo->costo_personalizado,
                        'costo_efectivo' => $costo / $validated['cantidad_piezas'], // Por pieza
                        'costo_total' => $costo * $validated['cantidad_piezas'],
                        'tiempo_base_min' => $proceso->tiempo_base_min,
                        'tiempo_personalizado_min' => $nodo->tiempo_personalizado_min,
                        'tiempo_efectivo_min' => $tiempo,
                        'merma_porcentaje' => $proceso->merma_porcentaje,
                        'es_paralelo' => $proceso->es_paralelo,
                        'es_opcional' => $proceso->es_opcional
                    ];
                }

                // Agregar tiempo de procesos paralelos restantes
                if (!empty($procesosParalelos)) {
                    $tiempoTotal += max($procesosParalelos);
                }

                $costoTotal = $costoMateriales + $costoProcesos;

                // 4. Guardar cálculo
                // Marcar cálculos anteriores como no actuales
                CalculoVariante::where('id_variante_estilo', $variante->id)
                              ->update(['es_actual' => false]);

                $version = CalculoVariante::where('id_variante_estilo', $variante->id)->max('version') + 1;

                $calculo = CalculoVariante::create([
                    'id_variante_estilo' => $variante->id,
                    'id_flujo_estilo' => $flujoId,
                    'costo_materiales' => $costoMateriales,
                    'costo_procesos' => $costoProcesos,
                    'costo_total' => $costoTotal,
                    'tiempo_total_min' => $tiempoTotal,
                    'version' => $version,
                    'es_actual' => true
                ]);

                // 5. Actualizar variante
                $variante->update([
                    'costo_calculado' => $costoTotal,
                    'tiempo_calculado_min' => $tiempoTotal
                ]);

                DB::commit();

                $resultado = [
                    'calculo_id' => $calculo->id,
                    'estilo' => [
                        'id' => $estilo->id,
                        'codigo' => $estilo->codigo,
                        'nombre' => $estilo->nombre,
                        'tipo_producto' => $estilo->tipo_producto
                    ],
                    'variante' => [
                        'id' => $variante->id,
                        'codigo_sku' => $variante->codigo_sku,
                        'color' => $color->nombre,
                        'talla' => $talla->nombre,
                        'cantidad_piezas' => $validated['cantidad_piezas'],
                        'multiplicador_talla' => $multiplicadorTalla
                    ],
                    'flujo' => [
                        'id' => $flujo->id,
                        'nombre' => $flujo->nombre,
                        'version' => $flujo->version
                    ],
                    'costos' => [
                        'materiales' => $costoMateriales,
                        'procesos' => $costoProcesos,
                        'total' => $costoTotal,
                        'por_pieza' => $costoTotal / $validated['cantidad_piezas']
                    ],
                    'tiempo' => [
                        'total_minutos' => $tiempoTotal,
                        'por_pieza_minutos' => $tiempoTotal // El tiempo no se multiplica por cantidad
                    ],
                    'bom' => $bomCalculado,
                    'flujo_procesos' => $flujoCalculado,
                    'estadisticas' => [
                        'total_materiales' => count($bomCalculado),
                        'materiales_criticos' => count(array_filter($bomCalculado, fn($item) => $item['es_critico'])),
                        'total_procesos' => count($flujoCalculado),
                        'procesos_paralelos' => count(array_filter($flujoCalculado, fn($item) => $item['es_paralelo'])),
                        'procesos_opcionales' => count(array_filter($flujoCalculado, fn($item) => $item['es_opcional'])),
                        'porcentaje_costo_materiales' => $costoTotal > 0 ? ($costoMateriales / $costoTotal) * 100 : 0,
                        'porcentaje_costo_procesos' => $costoTotal > 0 ? ($costoProcesos / $costoTotal) * 100 : 0
                    ],
                    'fecha_calculo' => now()->toISOString()
                ];

                return response()->json([
                    'success' => true,
                    'message' => 'Variante calculada exitosamente',
                    'data' => $resultado
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Estilo, color o talla no encontrada'
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
                'message' => 'Error al calcular variante',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener historial de cálculos de una variante
     */
    public function obtenerHistorial(string $estiloId, string $colorId, string $tallaId): JsonResponse
    {
        try {
            $estilo = Estilo::findOrFail($estiloId);
            $color = Color::findOrFail($colorId);
            $talla = Talla::findOrFail($tallaId);

            $variante = VarianteEstilo::where('id_estilo', $estiloId)
                                    ->where('id_color', $colorId)
                                    ->where('id_talla', $tallaId)
                                    ->first();

            if (!$variante) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron cálculos para esta variante'
                ], 404);
            }

            $calculos = CalculoVariante::porVariante($variante->id)
                                     ->with(['flujoEstilo'])
                                     ->orderByDesc('version')
                                     ->get();

            $historial = $calculos->map(function($calculo) {
                return [
                    'id' => $calculo->id,
                    'version' => $calculo->version,
                    'es_actual' => $calculo->es_actual,
                    'fecha_calculo' => $calculo->fecha_calculo,
                    'flujo' => [
                        'id' => $calculo->flujoEstilo->id,
                        'nombre' => $calculo->flujoEstilo->nombre,
                        'version' => $calculo->flujoEstilo->version
                    ],
                    'costos' => [
                        'materiales' => $calculo->costo_materiales,
                        'procesos' => $calculo->costo_procesos,
                        'total' => $calculo->costo_total
                    ],
                    'tiempo_total_min' => $calculo->tiempo_total_min,
                    'porcentajes' => [
                        'materiales' => $calculo->porcentaje_costo_materiales,
                        'procesos' => $calculo->porcentaje_costo_procesos
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'estilo' => $estilo,
                    'variante' => [
                        'color' => $color->nombre,
                        'talla' => $talla->nombre,
                        'codigo_sku' => $variante->codigo_sku
                    ],
                    'historial' => $historial,
                    'calculo_actual' => $historial->where('es_actual', true)->first(),
                    'total_calculos' => $historial->count()
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Estilo, color o talla no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Comparar dos cálculos de variantes
     */
    public function compararCalculos(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'calculo_id_1' => 'required|exists:calculos_variantes,id',
                'calculo_id_2' => 'required|exists:calculos_variantes,id'
            ]);

            $calculo1 = CalculoVariante::with(['varianteEstilo.estilo', 'flujoEstilo'])->findOrFail($validated['calculo_id_1']);
            $calculo2 = CalculoVariante::with(['varianteEstilo.estilo', 'flujoEstilo'])->findOrFail($validated['calculo_id_2']);

            $comparacion = [
                'calculo_1' => [
                    'id' => $calculo1->id,
                    'version' => $calculo1->version,
                    'fecha' => $calculo1->fecha_calculo,
                    'flujo' => $calculo1->flujoEstilo->nombre,
                    'costos' => [
                        'materiales' => $calculo1->costo_materiales,
                        'procesos' => $calculo1->costo_procesos,
                        'total' => $calculo1->costo_total
                    ],
                    'tiempo' => $calculo1->tiempo_total_min
                ],
                'calculo_2' => [
                    'id' => $calculo2->id,
                    'version' => $calculo2->version,
                    'fecha' => $calculo2->fecha_calculo,
                    'flujo' => $calculo2->flujoEstilo->nombre,
                    'costos' => [
                        'materiales' => $calculo2->costo_materiales,
                        'procesos' => $calculo2->costo_procesos,
                        'total' => $calculo2->costo_total
                    ],
                    'tiempo' => $calculo2->tiempo_total_min
                ],
                'diferencias' => [
                    'costo_materiales' => $calculo2->costo_materiales - $calculo1->costo_materiales,
                    'costo_procesos' => $calculo2->costo_procesos - $calculo1->costo_procesos,
                    'costo_total' => $calculo2->costo_total - $calculo1->costo_total,
                    'tiempo_total' => $calculo2->tiempo_total_min - $calculo1->tiempo_total_min,
                    'porcentaje_cambio_costo' => $calculo1->costo_total > 0 ? 
                        (($calculo2->costo_total - $calculo1->costo_total) / $calculo1->costo_total) * 100 : 0,
                    'porcentaje_cambio_tiempo' => $calculo1->tiempo_total_min > 0 ? 
                        (($calculo2->tiempo_total_min - $calculo1->tiempo_total_min) / $calculo1->tiempo_total_min) * 100 : 0
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $comparacion
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al comparar cálculos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar SKU automáticamente
     */
    private function generarSKU(Estilo $estilo, Color $color, Talla $talla): string
    {
        $codigoEstilo = $estilo->codigo;
        $codigoColor = $color->codigo_hex ? substr($color->codigo_hex, 1, 3) : substr($color->nombre, 0, 3);
        $codigoTalla = $talla->codigo;
        
        return strtoupper($codigoEstilo . '-' . $codigoColor . '-' . $codigoTalla);
    }

    /**
     * Obtener resumen de cálculos por estilo
     */
    public function getResumenPorEstilo(string $estiloId): JsonResponse
    {
        try {
            $estilo = Estilo::with(['variantes.calculos' => function($query) {
                $query->where('es_actual', true);
            }])->findOrFail($estiloId);

            $calculosActuales = $estilo->variantes->flatMap->calculos;

            if ($calculosActuales->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'estilo' => $estilo,
                        'mensaje' => 'No hay cálculos disponibles para este estilo'
                    ]
                ]);
            }

            $resumen = [
                'estilo' => $estilo,
                'total_variantes' => $estilo->variantes->count(),
                'variantes_calculadas' => $calculosActuales->count(),
                'costos' => [
                    'promedio' => $calculosActuales->avg('costo_total'),
                    'minimo' => $calculosActuales->min('costo_total'),
                    'maximo' => $calculosActuales->max('costo_total'),
                    'promedio_materiales' => $calculosActuales->avg('costo_materiales'),
                    'promedio_procesos' => $calculosActuales->avg('costo_procesos')
                ],
                'tiempos' => [
                    'promedio_min' => $calculosActuales->avg('tiempo_total_min'),
                    'minimo_min' => $calculosActuales->min('tiempo_total_min'),
                    'maximo_min' => $calculosActuales->max('tiempo_total_min')
                ],
                'variante_mas_costosa' => $calculosActuales->sortByDesc('costo_total')->first(),
                'variante_mas_economica' => $calculosActuales->sortBy('costo_total')->first(),
                'fecha_ultima_actualizacion' => $calculosActuales->max('fecha_calculo')
            ];

            return response()->json([
                'success' => true,
                'data' => $resumen
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Estilo no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener resumen',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
