<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Estilo;

class EstiloController
{
    /**
     * Listar estilos con filtros
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Estilo::with(['variantes.color']);

            // Filtros
            if ($request->has('temporada')) {
                $query->porTemporada($request->temporada);
            }

            if ($request->has('año_produccion')) {
                $query->porAño($request->año_produccion);
            }

            if ($request->has('activos_solo')) {
                $query->activos();
            }

            if ($request->has('en_desarrollo')) {
                $query->enDesarrollo();
            }

            if ($request->has('buscar')) {
                $query->where('nombre', 'like', '%' . $request->buscar . '%');
            }

            $estilos = $query->orderBy('nombre')->paginate(12);

            return response()->json([
                'success' => true,
                'data' => $estilos
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los estilos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nuevo estilo
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'codigo' => 'required|string|max:50|unique:estilos,codigo',
                'nombre' => 'required|string|max:100|unique:estilos,nombre',
                'descripcion' => 'nullable|string',
                'temporada' => 'required|in:primavera,verano,otoño,invierno',
                'año_produccion' => 'required|integer|min:2020|max:2030',
                'costo_objetivo' => 'nullable|numeric|min:0',
                'tiempo_objetivo_min' => 'nullable|numeric|min:0',
                'estado' => 'nullable|in:activo,inactivo,desarrollo'
            ]);

            $estilo = Estilo::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Estilo creado exitosamente',
                'data' => $estilo
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
                'message' => 'Error al crear el estilo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar estilo específico
     */
    public function show(string $id): JsonResponse
    {
        try {
            $estilo = Estilo::with([
                'variantes.color',
                'flujos.nodos.proceso',
                'bomItems.material.categoria'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $estilo
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Estilo no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el estilo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar estilo
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $estilo = Estilo::findOrFail($id);

            $request->validate([
                'codigo' => 'sometimes|string|max:50|unique:estilos,codigo,' . $id,
                'nombre' => 'sometimes|string|max:100|unique:estilos,nombre,' . $id,
                'descripcion' => 'nullable|string',
                'temporada' => 'sometimes|in:primavera,verano,otoño,invierno',
                'año_produccion' => 'sometimes|integer|min:2020|max:2030',
                'costo_objetivo' => 'nullable|numeric|min:0',
                'tiempo_objetivo_min' => 'nullable|numeric|min:0',
                'estado' => 'sometimes|in:activo,inactivo,desarrollo'
            ]);

            $estilo->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Estilo actualizado exitosamente',
                'data' => $estilo
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Estilo no encontrado'
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
                'message' => 'Error al actualizar el estilo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar estilo
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $estilo = Estilo::findOrFail($id);

            // Verificar si tiene variantes o flujos asociados
            if ($estilo->variantes()->count() > 0 || $estilo->flujos()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el estilo porque tiene variantes o flujos asociados'
                ], 400);
            }

            $estilo->update(['estado' => 'inactivo']);

            return response()->json([
                'success' => true,
                'message' => 'Estilo eliminado exitosamente'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Estilo no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el estilo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcular costos del estilo
     */
    public function calcularCostos(string $id): JsonResponse
    {
        try {
            $estilo = Estilo::with(['bomItems.material', 'flujos.nodos.proceso'])
                ->findOrFail($id);

            // Costo de materiales (BOM)
            $costoMateriales = $estilo->bomItems->sum(function ($bomItem) {
                return $bomItem->cantidad_base * $bomItem->material->costo_base;
            });

            // Costo de procesos
            $costoProcesos = 0;
            foreach ($estilo->flujos as $flujo) {
                foreach ($flujo->nodos as $nodo) {
                    $costoProcesos += $nodo->getCostoEfectivoAttribute();
                }
            }

            $costoTotal = $costoMateriales + $costoProcesos;
            
            // Comparar con objetivo si está definido
            $diferenciaCosto = null;
            if ($estilo->costo_objetivo) {
                $diferenciaCosto = $costoTotal - $estilo->costo_objetivo;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'costo_materiales' => round($costoMateriales, 2),
                    'costo_procesos' => round($costoProcesos, 2),
                    'costo_total' => round($costoTotal, 2),
                    'costo_objetivo' => $estilo->costo_objetivo,
                    'diferencia_costo' => $diferenciaCosto ? round($diferenciaCosto, 2) : null,
                    'estado_costo' => $diferenciaCosto ? ($diferenciaCosto <= 0 ? 'dentro_objetivo' : 'sobre_objetivo') : 'sin_objetivo'
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
                'message' => 'Error al calcular costos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcular costos y tiempos por variante específica (color/talla)
     */
    public function calcularPorVariante(Request $request, string $id): JsonResponse
    {
        try {
            $request->validate([
                'color' => 'nullable|string',
                'talla' => 'required|string|in:XS,S,M,L,XL,XXL',
                'cantidad_piezas' => 'integer|min:1'
            ]);

            $estilo = Estilo::with([
                'bomItems.material.categoria',
                'flujos.nodos.proceso'
            ])->findOrFail($id);

            $talla = $request->talla;
            $cantidadPiezas = $request->cantidad_piezas ?? 1;

            // Multiplicadores por talla para materiales
            $tallaMultipliers = [
                'XS' => 0.90,
                'S' => 0.95,
                'M' => 1.00,
                'L' => 1.05,
                'XL' => 1.10,
                'XXL' => 1.15
            ];

            $multiplier = $tallaMultipliers[$talla] ?? 1.0;

            // Calcular costos de materiales (BOM)
            $costoMateriales = 0;
            $bomItems = [];

            foreach ($estilo->bomItems as $bomItem) {
                $cantidadAjustada = $bomItem->cantidad_base * $multiplier;
                $cantidadConMerma = $cantidadAjustada * 1.02; // 2% merma por defecto
                $cantidadTotal = $cantidadConMerma * $cantidadPiezas;
                $costoTotal = $cantidadTotal * $bomItem->material->costo_base;
                $costoMateriales += $costoTotal;

                $bomItems[] = [
                    'id' => $bomItem->material->codigo ?? "MAT-{$bomItem->id_material}",
                    'description' => $bomItem->material->nombre,
                    'unit' => $bomItem->material->unidadMedida->simbolo ?? 'ud',
                    'quantity' => round($cantidadTotal, 4),
                    'cost' => round($costoTotal, 2),
                    'type' => $bomItem->material->tipo ?? 'general',
                    'color' => $request->color
                ];
            }

            // Calcular costos de procesos (flujos)
            $costoProcesos = 0;
            $tiempoTotal = 0;

            foreach ($estilo->flujos as $flujo) {
                foreach ($flujo->nodos as $nodo) {
                    $tiempoProceso = $nodo->getTiempoEfectivoAttribute();
                    $costoProceso = $nodo->getCostoEfectivoAttribute();
                    
                    $tiempoTotal += $tiempoProceso * $cantidadPiezas;
                    $costoProcesos += $costoProceso * $cantidadPiezas;
                }
            }

            $costoTotal = $costoMateriales + $costoProcesos;

            return response()->json([
                'success' => true,
                'data' => [
                    'estilo_id' => $id,
                    'estilo_nombre' => $estilo->nombre,
                    'variante' => [
                        'color' => $request->color ?? 'Natural',
                        'talla' => $talla,
                        'cantidad_piezas' => $cantidadPiezas
                    ],
                    'total_cost' => round($costoTotal, 2),
                    'material_cost' => round($costoMateriales, 2),
                    'process_cost' => round($costoProcesos, 2),
                    'total_time' => round($tiempoTotal, 2),
                    'cost_per_piece' => round($costoTotal / $cantidadPiezas, 2),
                    'bom_items' => $bomItems,
                    'multipliers' => [
                        'talla_multiplier' => $multiplier
                    ]
                ]
            ]);

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
                'message' => 'Error al calcular variante',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
