<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\FlujoEstilo;
use App\Models\FlujoNodoProceso;
use App\Models\FlujoConexion;

class FlujoController
{
    /**
     * Listar flujos por estilo
     */
    public function listarFlujosPorEstilo(string $estiloId): JsonResponse
    {
        try {
            $flujos = FlujoEstilo::where('id_estilo', $estiloId)
                ->orderBy('es_actual', 'desc')
                ->orderBy('version', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $flujos
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener flujos del estilo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener flujo completo para @xyflow/react
     */
    public function obtenerFlujo(string $estiloId, string $flujoId): JsonResponse
    {
        try {
            $flujo = FlujoEstilo::with([
                'nodos.proceso.tipoProceso',
                'conexiones.nodoOrigen.proceso',
                'conexiones.nodoDestino.proceso'
            ])->where('id_estilo', $estiloId)
              ->findOrFail($flujoId);

            $nodos = $flujo->nodos->map(function ($nodo) {
                $proceso = $nodo->proceso;
                return [
                    'id' => (string) $nodo->id,
                    'type' => 'customNode',
                    'pos_x' => (float) $nodo->pos_x,
                    'pos_y' => (float) $nodo->pos_y,
                    'position' => [ // compat frontend
                        'x' => (float) $nodo->pos_x,
                        'y' => (float) $nodo->pos_y,
                    ],
                    'data' => [
                        'id_proceso' => $nodo->id_proceso,
                        'nombre' => $proceso->nombre,
                        'descripcion' => $proceso->descripcion,
                        'tiempo_base_min' => (float) ($proceso->tiempo_base_min ?? 0),
                        'costo_base' => (float) ($proceso->costo_base ?? 0),
                        'tipo_proceso' => $proceso->tipoProceso ? [
                            'nombre' => $proceso->tipoProceso->nombre,
                            'color_hex' => $proceso->tipoProceso->color_hex,
                        ] : null,
                        'orden_secuencia' => $nodo->orden_secuencia,
                        'es_paralelo' => (bool) ($proceso->es_paralelo ?? false),
                        'tiempo_personalizado_min' => $nodo->tiempo_personalizado_min,
                        'costo_personalizado' => $nodo->costo_personalizado,
                        'es_opcional' => (bool) $nodo->es_opcional,
                        'es_punto_inicio' => (bool) $nodo->es_punto_inicio,
                        'es_punto_final' => (bool) $nodo->es_punto_final,
                        'notas' => $nodo->notas,
                    ],
                    'style' => [
                        'width' => (float) ($nodo->ancho ?? 200),
                        'height' => (float) ($nodo->alto ?? 80),
                    ]
                ];
            });

            $conexiones = $flujo->conexiones->map(function ($conexion) {
                return [
                    'id' => (string) $conexion->id,
                    'source' => (string) $conexion->id_nodo_origen,
                    'target' => (string) $conexion->id_nodo_destino,
                    'type' => $conexion->tipo_conexion === 'secuencial' ? 'smoothstep' : 'default',
                    'data' => [
                        'condicion_activacion' => $conexion->condicion_activacion,
                        'orden_prioridad' => $conexion->orden_prioridad,
                        'etiqueta' => $conexion->etiqueta,
                        'estilo_linea' => $conexion->estilo_linea,
                        'color_linea' => $conexion->color_linea,
                        'es_animada' => (bool) $conexion->es_animada,
                    ],
                    'animated' => (bool) $conexion->es_animada,
                    'style' => [ 'stroke' => $conexion->color_linea ?? '#64748B' ]
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'flujo' => $flujo->only(['id','id_estilo','nombre','descripcion','version','estado','costo_total_calculado','tiempo_total_calculado','es_actual']),
                    'nodes' => $nodos,
                    'edges' => $conexiones
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Flujo no encontrado'
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
     * Guardar flujo desde @xyflow/react
     */
    public function guardarFlujo(Request $request, string $estiloId): JsonResponse
    {
        try {
            $request->validate([
                'nombre' => 'required|string|max:100',
                'descripcion' => 'nullable|string',
                'version' => 'nullable|string|max:20',
                'nodes' => 'required|array',
                'edges' => 'required|array'
            ]);

            $flujo = FlujoEstilo::create([
                'id_estilo' => $estiloId,
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'version' => $request->version ?? '1.0',
                'estado' => 'activo',
                'es_actual' => false
            ]);

            $mapIds = [];
            foreach ($request->nodes as $node) {
                $posX = $node['pos_x'] ?? ($node['position']['x'] ?? 0);
                $posY = $node['pos_y'] ?? ($node['position']['y'] ?? 0);
                $orden = $node['data']['orden_secuencia'] ?? ($node['data']['orden_ejecucion'] ?? 1);
                $nodo = FlujoNodoProceso::create([
                    'id_flujo_estilo' => $flujo->id,
                    'id_proceso' => $node['data']['id_proceso'],
                    'pos_x' => $posX,
                    'pos_y' => $posY,
                    'ancho' => $node['style']['width'] ?? 200,
                    'alto' => $node['style']['height'] ?? 80,
                    'orden_secuencia' => $orden,
                    'tiempo_personalizado_min' => $node['data']['tiempo_personalizado_min'] ?? null,
                    'costo_personalizado' => $node['data']['costo_personalizado'] ?? null,
                    'es_opcional' => $node['data']['es_opcional'] ?? false,
                    'es_punto_inicio' => $node['data']['es_punto_inicio'] ?? false,
                    'es_punto_final' => $node['data']['es_punto_final'] ?? false,
                    'notas' => $node['data']['notas'] ?? null,
                    'estado' => 'activo'
                ]);
                $mapIds[$node['id']] = $nodo->id;
            }

            foreach ($request->edges as $edge) {
                FlujoConexion::create([
                    'id_flujo_estilo' => $flujo->id,
                    'id_nodo_origen' => $mapIds[$edge['source']] ?? null,
                    'id_nodo_destino' => $mapIds[$edge['target']] ?? null,
                    'tipo_conexion' => $edge['data']['tipo_conexion'] ?? 'secuencial',
                    'condicion_activacion' => $edge['data']['condicion_activacion'] ?? null,
                    'etiqueta' => $edge['data']['etiqueta'] ?? null,
                    'estilo_linea' => $edge['data']['estilo_linea'] ?? 'solida',
                    'color_linea' => $edge['data']['color_linea'] ?? '#64748B',
                    'es_animada' => $edge['data']['es_animada'] ?? false,
                    'orden_prioridad' => $edge['data']['orden_prioridad'] ?? 1,
                    'estado' => 'activo'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Flujo guardado exitosamente',
                'data' => $flujo->load(['nodos.proceso.tipoProceso', 'conexiones'])
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
                'message' => 'Error al guardar el flujo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar posiciones de nodos
     */
    public function actualizarPosiciones(Request $request, string $flujoId): JsonResponse
    {
        try {
            // Aceptar formato nuevo (nodos[{id,pos_x,pos_y}]) o antiguo (nodes[].position.x)
            $payloadNodes = $request->get('nodos') ?? $request->get('nodes');
            if (!is_array($payloadNodes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Formato de nodos inválido'
                ], 422);
            }

            foreach ($payloadNodes as $nodeData) {
                $id = $nodeData['id'] ?? null;
                if (!$id) continue;
                $x = $nodeData['pos_x'] ?? ($nodeData['position']['x'] ?? null);
                $y = $nodeData['pos_y'] ?? ($nodeData['position']['y'] ?? null);
                if ($x === null || $y === null) continue;

                FlujoNodoProceso::where('id', $id)
                    ->where('id_flujo_estilo', $flujoId)
                    ->update([
                        'pos_x' => $x,
                        'pos_y' => $y
                    ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Posiciones actualizadas'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar posiciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar flujo
     */
    public function eliminarFlujo(string $flujoId): JsonResponse
    {
        try {
            $flujo = FlujoEstilo::findOrFail($flujoId);

            // Eliminar conexiones
            $flujo->conexiones()->delete();

            // Eliminar nodos
            $flujo->nodos()->delete();

            // Eliminar flujo
            $flujo->delete();
            
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
     * Calcular tiempo total del flujo
     */
    public function calcularTiempoTotal(string $flujoId): JsonResponse
    {
        try {
            $flujo = FlujoEstilo::with('nodos.proceso')->findOrFail($flujoId);
            $tiempoTotalMin = $flujo->nodos->sum(function ($nodo) { return $nodo->getTiempoEfectivoAttribute(); });
            $costoTotal = $flujo->nodos->sum(function ($nodo) { return $nodo->getCostoEfectivoAttribute(); });

            return response()->json([
                'success' => true,
                'data' => [
                    'tiempo_total_min' => round($tiempoTotalMin, 2),
                    'costo_total' => round($costoTotal, 4),
                    'numero_procesos' => $flujo->nodos->count()
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Flujo no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al calcular tiempo',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
