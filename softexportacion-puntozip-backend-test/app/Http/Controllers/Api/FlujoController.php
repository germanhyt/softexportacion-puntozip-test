<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlujoEstilo;
use App\Models\FlujoNodoProceso;
use App\Models\FlujoConexion;
use App\Models\Estilo;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class FlujoController extends Controller
{
    /**
     * Listar flujos por estilo
     */
    public function listarFlujosPorEstilo(string $estiloId): JsonResponse
    {
        try {
            $estilo = Estilo::findOrFail($estiloId);
            
            $flujos = FlujoEstilo::porEstilo($estiloId)
                                ->with(['nodos.proceso', 'conexiones'])
                                ->orderByDesc('version')
                                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'estilo' => $estilo,
                    'flujos' => $flujos,
                    'flujo_actual' => $flujos->where('es_actual', true)->first()
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
                'message' => 'Error al obtener flujos del estilo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener flujo completo para el editor visual (ReactFlow)
     */
    public function obtenerFlujo(string $estiloId, string $flujoId): JsonResponse
    {
        try {
            $estilo = Estilo::findOrFail($estiloId);
            $flujo = FlujoEstilo::with([
                'nodos.proceso.tipoProceso',
                'conexiones.nodoOrigen',
                'conexiones.nodoDestino'
            ])->findOrFail($flujoId);

            // Verificar que el flujo pertenece al estilo
            if ($flujo->id_estilo != $estiloId) {
                return response()->json([
                    'success' => false,
                    'message' => 'El flujo no pertenece al estilo especificado'
                ], 400);
            }

            $datosReactFlow = $flujo->getDatosParaReactFlow();
            $validacion = $flujo->validarConsistencia();

            return response()->json([
                'success' => true,
                'data' => [
                    'estilo' => $estilo,
                    'flujo' => $datosReactFlow,
                    'validacion' => $validacion
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Estilo o flujo no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el flujo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guardar nuevo flujo desde el editor visual
     */
    public function guardarFlujo(Request $request, string $estiloId): JsonResponse
    {
        try {
            $estilo = Estilo::findOrFail($estiloId);

            $validated = $request->validate([
                'nombre' => 'required|string|max:200',
                'nodes' => 'required|array',
                'nodes.*.id' => 'required|string',
                'nodes.*.position' => 'required|array',
                'nodes.*.position.x' => 'required|numeric',
                'nodes.*.position.y' => 'required|numeric',
                'nodes.*.data' => 'required|array',
                'nodes.*.data.id_proceso' => 'required|exists:procesos,id',
                'nodes.*.data.es_punto_inicio' => 'boolean',
                'nodes.*.data.es_punto_final' => 'boolean',
                'nodes.*.data.costo_personalizado' => 'nullable|numeric|min:0',
                'nodes.*.data.tiempo_personalizado_min' => 'nullable|numeric|min:0',
                'nodes.*.data.notas' => 'nullable|string',
                'edges' => 'required|array',
                'edges.*.id' => 'required|string',
                'edges.*.source' => 'required|string',
                'edges.*.target' => 'required|string',
                'edges.*.data' => 'nullable|array',
                'edges.*.data.tipo_conexion' => 'nullable|in:secuencial,condicional,paralelo',
                'edges.*.data.etiqueta' => 'nullable|string|max:100',
                'edges.*.data.condicion_activacion' => 'nullable|string|max:500'
            ]);

            DB::beginTransaction();

            try {
                // Crear flujo
                $version = FlujoEstilo::where('id_estilo', $estiloId)->max('version') + 1;
                
                $flujo = FlujoEstilo::create([
                    'id_estilo' => $estiloId,
                    'nombre' => $validated['nombre'],
                    'version' => $version,
                    'estado' => 'borrador'
                ]);

                // Mapear IDs de nodos ReactFlow a IDs de base de datos
                $mapeoNodos = [];
                $ordenSecuencia = 1;

                // Crear nodos
                foreach ($validated['nodes'] as $node) {
                    $nodo = FlujoNodoProceso::create([
                        'id_flujo_estilo' => $flujo->id,
                        'id_proceso' => $node['data']['id_proceso'],
                        'orden_secuencia' => $ordenSecuencia++,
                        'pos_x' => $node['position']['x'],
                        'pos_y' => $node['position']['y'],
                        'ancho' => $node['data']['ancho'] ?? 200,
                        'alto' => $node['data']['alto'] ?? 80,
                        'costo_personalizado' => $node['data']['costo_personalizado'] ?? null,
                        'tiempo_personalizado_min' => $node['data']['tiempo_personalizado_min'] ?? null,
                        'es_punto_inicio' => $node['data']['es_punto_inicio'] ?? false,
                        'es_punto_final' => $node['data']['es_punto_final'] ?? false,
                        'notas' => $node['data']['notas'] ?? null
                    ]);

                    $mapeoNodos[$node['id']] = $nodo->id;
                }

                // Crear conexiones
                foreach ($validated['edges'] as $edge) {
                    if (!isset($mapeoNodos[$edge['source']]) || !isset($mapeoNodos[$edge['target']])) {
                        continue; // Saltar conexiones con nodos inexistentes
                    }

                    FlujoConexion::create([
                        'id_flujo_estilo' => $flujo->id,
                        'id_nodo_origen' => $mapeoNodos[$edge['source']],
                        'id_nodo_destino' => $mapeoNodos[$edge['target']],
                        'tipo_conexion' => $edge['data']['tipo_conexion'] ?? 'secuencial',
                        'etiqueta' => $edge['data']['etiqueta'] ?? null,
                        'condicion_activacion' => $edge['data']['condicion_activacion'] ?? null,
                        'color_linea' => '#64748B',
                        'orden_prioridad' => 1
                    ]);
                }

                // Calcular totales del flujo
                $flujo->calcularTotales();

                // Validar consistencia
                $validacion = $flujo->validarConsistencia();

                if (!$validacion['es_valido']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'El flujo contiene errores de consistencia',
                        'errors' => $validacion['errores']
                    ], 422);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Flujo guardado exitosamente',
                    'data' => [
                        'flujo' => $flujo->load(['nodos.proceso', 'conexiones']),
                        'validacion' => $validacion
                    ]
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

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
                'message' => 'Error al guardar el flujo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar posiciones de nodos en tiempo real
     */
    public function actualizarPosiciones(Request $request, string $flujoId): JsonResponse
    {
        try {
            $flujo = FlujoEstilo::findOrFail($flujoId);

            $validated = $request->validate([
                'nodos' => 'required|array',
                'nodos.*.id' => 'required|exists:flujos_nodos_procesos,id',
                'nodos.*.pos_x' => 'required|numeric',
                'nodos.*.pos_y' => 'required|numeric'
            ]);

            foreach ($validated['nodos'] as $nodoData) {
                $nodo = FlujoNodoProceso::where('id', $nodoData['id'])
                                      ->where('id_flujo_estilo', $flujoId)
                                      ->first();

                if ($nodo) {
                    $nodo->actualizarPosicion($nodoData['pos_x'], $nodoData['pos_y']);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Posiciones actualizadas exitosamente'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Flujo no encontrado'
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
                'message' => 'Error al actualizar posiciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar flujo completo
     */
    public function eliminarFlujo(string $flujoId): JsonResponse
    {
        try {
            $flujo = FlujoEstilo::findOrFail($flujoId);

            // No permitir eliminar el flujo actual si es el único
            if ($flujo->es_actual) {
                $otrosFlujos = FlujoEstilo::where('id_estilo', $flujo->id_estilo)
                                        ->where('id', '!=', $flujoId)
                                        ->exists();

                if (!$otrosFlujos) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se puede eliminar el único flujo del estilo'
                    ], 400);
                }

                // Si hay otros flujos, marcar el más reciente como actual
                $flujoMasReciente = FlujoEstilo::where('id_estilo', $flujo->id_estilo)
                                             ->where('id', '!=', $flujoId)
                                             ->orderByDesc('version')
                                             ->first();

                if ($flujoMasReciente) {
                    $flujoMasReciente->marcarComoActual();
                }
            }

            $flujo->delete(); // Esto también eliminará nodos y conexiones por CASCADE

            return response()->json([
                'success' => true,
                'message' => 'Flujo eliminado exitosamente'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Flujo no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el flujo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcular tiempo y costo total del flujo
     */
    public function calcularTiempoTotal(string $flujoId): JsonResponse
    {
        try {
            $flujo = FlujoEstilo::with(['nodos.proceso'])->findOrFail($flujoId);
            $resultado = $flujo->calcularTotales();

            return response()->json([
                'success' => true,
                'data' => $resultado
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Flujo no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al calcular totales del flujo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar flujo como actual
     */
    public function marcarComoActual(string $flujoId): JsonResponse
    {
        try {
            $flujo = FlujoEstilo::findOrFail($flujoId);

            // Validar que el flujo esté en estado activo
            if ($flujo->estado !== 'activo') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden marcar como actuales los flujos en estado activo'
                ], 400);
            }

            $flujo->marcarComoActual();

            return response()->json([
                'success' => true,
                'message' => 'Flujo marcado como actual exitosamente',
                'data' => $flujo
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Flujo no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar flujo como actual',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activar flujo (cambiar de borrador a activo)
     */
    public function activarFlujo(string $flujoId): JsonResponse
    {
        try {
            $flujo = FlujoEstilo::findOrFail($flujoId);

            // Validar consistencia antes de activar
            $validacion = $flujo->validarConsistencia();
            if (!$validacion['es_valido']) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede activar el flujo porque contiene errores',
                    'errors' => $validacion['errores']
                ], 422);
            }

            $flujo->update(['estado' => 'activo']);

            return response()->json([
                'success' => true,
                'message' => 'Flujo activado exitosamente',
                'data' => $flujo
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Flujo no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al activar el flujo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Duplicar flujo con nueva versión
     */
    public function duplicarFlujo(string $flujoId): JsonResponse
    {
        try {
            $flujoOriginal = FlujoEstilo::with(['nodos', 'conexiones'])->findOrFail($flujoId);
            $nuevoFlujo = $flujoOriginal->duplicarConNuevaVersion();

            return response()->json([
                'success' => true,
                'message' => 'Flujo duplicado exitosamente',
                'data' => $nuevoFlujo->load(['nodos.proceso', 'conexiones'])
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Flujo no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al duplicar el flujo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validar consistencia del flujo
     */
    public function validarConsistencia(string $flujoId): JsonResponse
    {
        try {
            $flujo = FlujoEstilo::with(['nodos.proceso', 'conexiones'])->findOrFail($flujoId);
            $validacion = $flujo->validarConsistencia();

            return response()->json([
                'success' => true,
                'data' => $validacion
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Flujo no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al validar consistencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener plantilla de flujo vacío para ReactFlow
     */
    public function getPlantillaVacia(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'flujo' => [
                        'id' => null,
                        'nombre' => 'Nuevo Flujo',
                        'version' => 1,
                        'estado' => 'borrador',
                        'es_actual' => false,
                        'costo_total' => 0,
                        'tiempo_total' => 0
                    ],
                    'nodes' => [],
                    'edges' => []
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener plantilla',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
