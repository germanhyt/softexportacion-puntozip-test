<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proceso;
use App\Models\TipoProceso;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ProcesoController extends Controller
{
    /**
     * Listar procesos con filtros y paginación
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Proceso::with(['tipoProceso', 'inputs', 'outputs']);

            // Aplicar filtros
            if ($request->has('id_tipo_proceso') && $request->id_tipo_proceso) {
                $query->porTipo($request->id_tipo_proceso);
            }

            if ($request->has('estado') && $request->estado !== 'todos') {
                if ($request->estado === 'activos') {
                    $query->activos();
                } else {
                    $query->inactivos();
                }
            }

            if ($request->has('es_paralelo') && $request->es_paralelo !== 'todos') {
                if ($request->es_paralelo === 'si') {
                    $query->paralelos();
                } else {
                    $query->secuenciales();
                }
            }

            if ($request->has('es_opcional') && $request->es_opcional !== 'todos') {
                if ($request->es_opcional === 'si') {
                    $query->opcionales();
                } else {
                    $query->obligatorios();
                }
            }

            if ($request->has('requiere_color') && $request->requiere_color !== 'todos') {
                if ($request->requiere_color === 'si') {
                    $query->queRequierenColor();
                }
            }

            if ($request->has('buscar') && $request->buscar) {
                $query->buscar($request->buscar);
            }

            // Ordenamiento
            $ordenPor = $request->get('orden_por', 'nombre');
            $direccion = $request->get('direccion', 'asc');
            $query->orderBy($ordenPor, $direccion);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $procesos = $query->paginate($perPage);

            // Agregar información adicional
            $procesos->getCollection()->transform(function ($proceso) {
                $proceso->info_completa = $proceso->getInfoCompleta();
                $proceso->formato_reactflow = $proceso->getFormatoReactFlow();
                return $proceso;
            });

            return response()->json([
                'success' => true,
                'data' => $procesos,
                'filtros_aplicados' => [
                    'id_tipo_proceso' => $request->id_tipo_proceso,
                    'estado' => $request->estado,
                    'es_paralelo' => $request->es_paralelo,
                    'es_opcional' => $request->es_opcional,
                    'requiere_color' => $request->requiere_color,
                    'buscar' => $request->buscar
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los procesos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nuevo proceso
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'codigo' => 'required|string|max:50|unique:procesos,codigo',
                'nombre' => 'required|string|max:200',
                'descripcion' => 'nullable|string',
                'sop' => 'nullable|string',
                'id_tipo_proceso' => 'required|exists:tipos_procesos,id',
                'costo_base' => 'required|numeric|min:0',
                'tiempo_base_min' => 'required|numeric|min:0',
                'merma_porcentaje' => 'nullable|numeric|min:0|max:100',
                'es_paralelo' => 'boolean',
                'es_opcional' => 'boolean',
                'requiere_color' => 'boolean',
                'estado' => 'nullable|in:activo,inactivo'
            ]);

            $validated['estado'] = $validated['estado'] ?? 'activo';
            $validated['merma_porcentaje'] = $validated['merma_porcentaje'] ?? 0;
            $validated['es_paralelo'] = $validated['es_paralelo'] ?? false;
            $validated['es_opcional'] = $validated['es_opcional'] ?? false;
            $validated['requiere_color'] = $validated['requiere_color'] ?? false;

            $proceso = Proceso::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Proceso creado exitosamente',
                'data' => $proceso->load(['tipoProceso', 'inputs', 'outputs'])
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
                'message' => 'Error al crear el proceso',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar proceso específico
     */
    public function show(string $id): JsonResponse
    {
        try {
            $proceso = Proceso::with([
                'tipoProceso',
                'inputs.material',
                'inputs.procesoOrigen',
                'outputs',
                'bomItems.estilo',
                'nodosFlujó.flujoEstilo'
            ])->findOrFail($id);

            $infoCompleta = $proceso->getInfoCompleta();

            return response()->json([
                'success' => true,
                'data' => [
                    'proceso' => $proceso,
                    'info_completa' => $infoCompleta,
                    'sop_formateado' => $proceso->getSopFormateado(),
                    'formato_reactflow' => $proceso->getFormatoReactFlow(),
                    'procesos_siguientes' => $proceso->getProcesosSiguientes(),
                    'procesos_anteriores' => $proceso->getProcesosAnteriores()
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Proceso no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el proceso',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar proceso
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $proceso = Proceso::findOrFail($id);

            $validated = $request->validate([
                'codigo' => 'sometimes|string|max:50|unique:procesos,codigo,' . $id,
                'nombre' => 'sometimes|string|max:200',
                'descripcion' => 'nullable|string',
                'sop' => 'nullable|string',
                'id_tipo_proceso' => 'sometimes|exists:tipos_procesos,id',
                'costo_base' => 'sometimes|numeric|min:0',
                'tiempo_base_min' => 'sometimes|numeric|min:0',
                'merma_porcentaje' => 'sometimes|numeric|min:0|max:100',
                'es_paralelo' => 'sometimes|boolean',
                'es_opcional' => 'sometimes|boolean',
                'requiere_color' => 'sometimes|boolean',
                'estado' => 'sometimes|in:activo,inactivo'
            ]);

            $proceso->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Proceso actualizado exitosamente',
                'data' => $proceso->load(['tipoProceso', 'inputs', 'outputs'])
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Proceso no encontrado'
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
                'message' => 'Error al actualizar el proceso',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar proceso (soft delete)
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $proceso = Proceso::findOrFail($id);

            // Verificar si está siendo usado en algún flujo
            $enUso = $proceso->nodosFlujó()->exists();
            if ($enUso) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el proceso porque está siendo usado en flujos de estilos'
                ], 400);
            }

            // Marcar como inactivo en lugar de eliminar
            $proceso->update(['estado' => 'inactivo']);

            return response()->json([
                'success' => true,
                'message' => 'Proceso marcado como inactivo exitosamente'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Proceso no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el proceso',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener tipos de proceso disponibles
     */
    public function getTiposProceso(): JsonResponse
    {
        try {
            $tipos = TipoProceso::activos()->orderBy('nombre')->get();
            
            return response()->json([
                'success' => true,
                'data' => $tipos
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de proceso',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener procesos por tipo específico
     */
    public function getPorTipo(string $nombreTipo): JsonResponse
    {
        try {
            $procesos = Proceso::porTipoNombre($nombreTipo);

            return response()->json([
                'success' => true,
                'data' => $procesos,
                'tipo' => $nombreTipo,
                'total' => $procesos->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener procesos por tipo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener procesos para ReactFlow (formato específico)
     */
    public function getParaReactFlow(): JsonResponse
    {
        try {
            $procesos = Proceso::with(['tipoProceso', 'inputs', 'outputs'])
                             ->activos()
                             ->get()
                             ->map(function($proceso) {
                                 return $proceso->getFormatoReactFlow();
                             });

            return response()->json([
                'success' => true,
                'data' => $procesos,
                'total' => $procesos->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener procesos para ReactFlow',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener SOP (Standard Operating Procedure) de un proceso
     */
    public function getSOP(string $id): JsonResponse
    {
        try {
            $proceso = Proceso::with(['tipoProceso', 'inputs', 'outputs'])->findOrFail($id);
            $sopFormateado = $proceso->getSopFormateado();

            return response()->json([
                'success' => true,
                'data' => $sopFormateado
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Proceso no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener SOP del proceso',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar compatibilidad entre procesos para ejecución paralela
     */
    public function verificarCompatibilidad(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'proceso1_id' => 'required|exists:procesos,id',
                'proceso2_id' => 'required|exists:procesos,id'
            ]);

            $proceso1 = Proceso::findOrFail($validated['proceso1_id']);
            $proceso2 = Proceso::findOrFail($validated['proceso2_id']);

            $puedeEjecutarse = $proceso1->puedeEjecutarseEnParalelocon($proceso2);

            return response()->json([
                'success' => true,
                'data' => [
                    'puede_ejecutarse_paralelo' => $puedeEjecutarse,
                    'proceso1' => [
                        'id' => $proceso1->id,
                        'nombre' => $proceso1->nombre,
                        'es_paralelo' => $proceso1->es_paralelo
                    ],
                    'proceso2' => [
                        'id' => $proceso2->id,
                        'nombre' => $proceso2->nombre,
                        'es_paralelo' => $proceso2->es_paralelo
                    ],
                    'razon' => $puedeEjecutarse ? 
                        'Los procesos pueden ejecutarse en paralelo' : 
                        'Los procesos no pueden ejecutarse en paralelo (dependencias o no configurados como paralelos)'
                ]
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
                'message' => 'Error al verificar compatibilidad',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener resumen de procesos
     */
    public function getResumen(): JsonResponse
    {
        try {
            $total = Proceso::count();
            $activos = Proceso::activos()->count();
            $paralelos = Proceso::paralelos()->count();
            $opcionales = Proceso::opcionales()->count();
            $queRequierenColor = Proceso::queRequierenColor()->count();

            $porTipo = Proceso::with('tipoProceso')
                            ->get()
                            ->groupBy('tipoProceso.nombre')
                            ->map->count();

            $costoPromedio = Proceso::activos()->avg('costo_base') ?? 0;
            $tiempoPromedio = Proceso::activos()->avg('tiempo_base_min') ?? 0;

            $procesosConMayorUso = Proceso::withCount('nodosFlujó')
                                        ->orderByDesc('nodos_flujo_count')
                                        ->limit(5)
                                        ->get(['id', 'codigo', 'nombre']);

            return response()->json([
                'success' => true,
                'data' => [
                    'totales' => [
                        'total' => $total,
                        'activos' => $activos,
                        'paralelos' => $paralelos,
                        'opcionales' => $opcionales,
                        'requieren_color' => $queRequierenColor
                    ],
                    'distribucion' => [
                        'por_tipo' => $porTipo
                    ],
                    'promedios' => [
                        'costo_base' => round($costoPromedio, 4),
                        'tiempo_base_min' => round($tiempoPromedio, 2)
                    ],
                    'estadisticas' => [
                        'procesos_mas_usados' => $procesosConMayorUso,
                        'porcentaje_paralelos' => $total > 0 ? round(($paralelos / $total) * 100, 2) : 0,
                        'porcentaje_opcionales' => $total > 0 ? round(($opcionales / $total) * 100, 2) : 0
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener resumen de procesos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
