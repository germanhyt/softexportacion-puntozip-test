<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlujoEstilo extends Model
{
    protected $table = 'flujos_estilos';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id_estilo',
        'nombre',
        'version',
        'costo_total_calculado',
        'tiempo_total_calculado',
        'es_actual',
        'estado'
    ];

    protected $casts = [
        'version' => 'integer',
        'costo_total_calculado' => 'decimal:4',
        'tiempo_total_calculado' => 'decimal:2',
        'es_actual' => 'boolean',
        'fecha_creacion' => 'datetime',
        'fecha_actualizacion' => 'datetime'
    ];

    const UPDATED_AT = 'fecha_actualizacion';
    const CREATED_AT = 'fecha_creacion';

    // Constantes para estados
    const ESTADO_ACTIVO = 'activo';
    const ESTADO_INACTIVO = 'inactivo';
    const ESTADO_BORRADOR = 'borrador';

    // ============================================================================
    // RELACIONES
    // ============================================================================

    public function estilo(): BelongsTo
    {
        return $this->belongsTo(Estilo::class, 'id_estilo');
    }

    public function nodos(): HasMany
    {
        return $this->hasMany(FlujoNodoProceso::class, 'id_flujo_estilo')->orderBy('orden_secuencia');
    }

    public function conexiones(): HasMany
    {
        return $this->hasMany(FlujoConexion::class, 'id_flujo_estilo');
    }

    public function calculosVariantes(): HasMany
    {
        return $this->hasMany(CalculoVariante::class, 'id_flujo_estilo');
    }

    // ============================================================================
    // SCOPES
    // ============================================================================

    public function scopeActivos($query)
    {
        return $query->where('estado', self::ESTADO_ACTIVO);
    }

    public function scopeBorradores($query)
    {
        return $query->where('estado', self::ESTADO_BORRADOR);
    }

    public function scopeActuales($query)
    {
        return $query->where('es_actual', true);
    }

    public function scopePorEstilo($query, $estiloId)
    {
        return $query->where('id_estilo', $estiloId);
    }

    // ============================================================================
    // MÉTODOS AUXILIARES
    // ============================================================================

    /**
     * Marcar este flujo como actual y desmarcar otros del mismo estilo
     */
    public function marcarComoActual()
    {
        // Desmarcar otros flujos del mismo estilo
        self::where('id_estilo', $this->id_estilo)
            ->where('id', '!=', $this->id)
            ->update(['es_actual' => false]);

        // Marcar este como actual
        $this->update(['es_actual' => true]);
    }

    /**
     * Obtener datos del flujo para ReactFlow
     */
    public function getDatosParaReactFlow()
    {
        $nodos = $this->nodos()->with(['proceso.tipoProceso'])->get();
        $conexiones = $this->conexiones()->with(['nodoOrigen', 'nodoDestino'])->get();

        $nodosReactFlow = $nodos->map(function ($nodo) {
            return [
                'id' => (string)$nodo->id,
                'type' => 'customNode',
                'position' => [
                    'x' => (float)$nodo->pos_x,
                    'y' => (float)$nodo->pos_y
                ],
                'data' => [
                    'id_proceso' => $nodo->id_proceso,
                    'codigo' => $nodo->proceso->codigo,
                    'nombre' => $nodo->proceso->nombre,
                    'descripcion' => $nodo->proceso->descripcion,
                    'tipo' => $nodo->proceso->tipoProceso->nombre ?? 'Sin tipo',
                    'costo_base' => $nodo->costo_personalizado ?? $nodo->proceso->costo_base,
                    'tiempo_base_min' => $nodo->tiempo_personalizado_min ?? $nodo->proceso->tiempo_base_min,
                    'es_paralelo' => $nodo->proceso->es_paralelo,
                    'es_opcional' => $nodo->proceso->es_opcional,
                    'requiere_color' => $nodo->proceso->requiere_color,
                    'es_punto_inicio' => $nodo->es_punto_inicio,
                    'es_punto_final' => $nodo->es_punto_final,
                    'orden_secuencia' => $nodo->orden_secuencia,
                    'color_tipo' => $nodo->proceso->tipoProceso->color_hex ?? '#E5E7EB',
                    'notas' => $nodo->notas,
                    'ancho' => (float)$nodo->ancho,
                    'alto' => (float)$nodo->alto
                ]
            ];
        });

        $conexionesReactFlow = $conexiones->map(function ($conexion) {
            return [
                'id' => (string)$conexion->id,
                'source' => (string)$conexion->id_nodo_origen,
                'target' => (string)$conexion->id_nodo_destino,
                'type' => 'smoothstep',
                'data' => [
                    'tipo_conexion' => $conexion->tipo_conexion,
                    'etiqueta' => $conexion->etiqueta,
                    'condicion_activacion' => $conexion->condicion_activacion
                ],
                'style' => [
                    'stroke' => $conexion->color_linea,
                    'strokeWidth' => 2,
                    'strokeDasharray' => $this->getEstiloLinea($conexion->estilo_linea)
                ],
                'animated' => $conexion->es_animada,
                'label' => $conexion->etiqueta
            ];
        });

        return [
            'flujo' => [
                'id' => $this->id,
                'nombre' => $this->nombre,
                'version' => $this->version,
                'estado' => $this->estado,
                'es_actual' => $this->es_actual,
                'costo_total' => $this->costo_total_calculado,
                'tiempo_total' => $this->tiempo_total_calculado
            ],
            'nodes' => $nodosReactFlow->toArray(),
            'edges' => $conexionesReactFlow->toArray()
        ];
    }

    /**
     * Obtener estilo de línea para CSS
     */
    private function getEstiloLinea($estiloLinea)
    {
        switch ($estiloLinea) {
            case 'punteada':
                return '5,5';
            case 'discontinua':
                return '10,5';
            default:
                return '0';
        }
    }

    /**
     * Calcular costos y tiempos totales del flujo
     */
    public function calcularTotales()
    {
        $nodos = $this->nodos()->with('proceso')->get();

        $costoTotal = 0;
        $tiempoTotal = 0;
        $tiempoParalelo = 0;
        $procesosParalelos = [];

        foreach ($nodos as $nodo) {
            $costo = $nodo->costo_personalizado ?? $nodo->proceso->costo_base;
            $tiempo = $nodo->tiempo_personalizado_min ?? $nodo->proceso->tiempo_base_min;

            // Aplicar merma si existe
            if ($nodo->proceso->merma_porcentaje > 0) {
                $costo *= (1 + ($nodo->proceso->merma_porcentaje / 100));
                $tiempo *= (1 + ($nodo->proceso->merma_porcentaje / 100));
            }

            $costoTotal += $costo;

            // Manejar tiempos de procesos paralelos
            if ($nodo->proceso->es_paralelo) {
                $procesosParalelos[] = $tiempo;
            } else {
                // Si hay procesos paralelos acumulados, tomar el máximo
                if (!empty($procesosParalelos)) {
                    $tiempoTotal += max($procesosParalelos);
                    $procesosParalelos = [];
                }
                $tiempoTotal += $tiempo;
            }
        }

        // Agregar tiempo de procesos paralelos restantes
        if (!empty($procesosParalelos)) {
            $tiempoTotal += max($procesosParalelos);
        }

        // Actualizar totales calculados
        $this->update([
            'costo_total_calculado' => $costoTotal,
            'tiempo_total_calculado' => $tiempoTotal
        ]);

        return [
            'costo_total' => $costoTotal,
            'tiempo_total' => $tiempoTotal,
            'desglose' => [
                'nodos_procesados' => $nodos->count(),
                'procesos_paralelos' => $nodos->where('proceso.es_paralelo', true)->count(),
                'procesos_secuenciales' => $nodos->where('proceso.es_paralelo', false)->count(),
                'procesos_opcionales' => $nodos->where('proceso.es_opcional', true)->count()
            ]
        ];
    }

    /**
     * Validar consistencia del flujo
     */
    public function validarConsistencia()
    {
        $errores = [];
        $nodos = $this->nodos()->with('proceso')->get();
        $conexiones = $this->conexiones()->get();

        // Validar que tenga al menos un nodo
        if ($nodos->isEmpty()) {
            $errores[] = 'El flujo debe tener al menos un proceso';
        }

        // Validar puntos de inicio y final
        $puntosInicio = $nodos->where('es_punto_inicio', true)->count();
        $puntosFinal = $nodos->where('es_punto_final', true)->count();

        if ($puntosInicio === 0) {
            $errores[] = 'El flujo debe tener al menos un punto de inicio';
        }

        if ($puntosFinal === 0) {
            $errores[] = 'El flujo debe tener al menos un punto final';
        }

        // Validar que todos los nodos tengan conexiones (excepto inicio y final)
        foreach ($nodos as $nodo) {
            if (
                // !$nodo->es_punto_inicio
                // &&
                !$nodo->es_punto_final
            ) {
                // $tieneEntrada = $conexiones->where('id_nodo_destino', $nodo->id)->isNotEmpty();
                $tieneSalida = $conexiones->where('id_nodo_origen', $nodo->id)->isNotEmpty();

                // if (!$tieneEntrada) {
                // $errores[] = "El proceso '{$nodo->proceso->nombre}' no tiene conexiones de entrada";
                // }

                if (!$tieneSalida) {
                    $errores[] = "El proceso '{$nodo->proceso->nombre}' no tiene conexiones de salida";
                }
            }
        }

        // Validar dependencias de procesos que requieren color
        $procesosConColor = $nodos->filter(function ($nodo) {
            return $nodo->proceso->requiere_color;
        });

        foreach ($procesosConColor as $nodo) {
            // Verificar que tenga materiales de tipo tinte en el BOM del estilo
            $tieneTintes = $this->estilo->bomItems()
                ->whereHas('material', function ($query) {
                    $query->where('tipo_material', 'tinte');
                })
                ->exists();

            if (!$tieneTintes) {
                $errores[] = "El proceso '{$nodo->proceso->nombre}' requiere color pero el estilo no tiene tintes en su BOM";
            }
        }

        return [
            'es_valido' => empty($errores),
            'errores' => $errores,
            'estadisticas' => [
                'total_nodos' => $nodos->count(),
                'total_conexiones' => $conexiones->count(),
                'puntos_inicio' => $puntosInicio,
                'puntos_final' => $puntosFinal,
                'procesos_paralelos' => $nodos->where('proceso.es_paralelo', true)->count(),
                'procesos_opcionales' => $nodos->where('proceso.es_opcional', true)->count()
            ]
        ];
    }

    /**
     * Métodos estáticos para obtener opciones
     */
    public static function getEstados()
    {
        return [
            self::ESTADO_ACTIVO,
            self::ESTADO_INACTIVO,
            self::ESTADO_BORRADOR
        ];
    }

    /**
     * Duplicar flujo con nueva versión
     */
    public function duplicarConNuevaVersion()
    {
        $nuevaVersion = self::where('id_estilo', $this->id_estilo)->max('version') + 1;

        // Crear nuevo flujo
        $nuevoFlujo = self::create([
            'id_estilo' => $this->id_estilo,
            'nombre' => $this->nombre . ' v' . $nuevaVersion,
            'version' => $nuevaVersion,
            'estado' => self::ESTADO_BORRADOR
        ]);

        // Copiar nodos
        foreach ($this->nodos as $nodo) {
            $nuevoNodo = $nuevoFlujo->nodos()->create([
                'id_proceso' => $nodo->id_proceso,
                'orden_secuencia' => $nodo->orden_secuencia,
                'pos_x' => $nodo->pos_x,
                'pos_y' => $nodo->pos_y,
                'ancho' => $nodo->ancho,
                'alto' => $nodo->alto,
                'costo_personalizado' => $nodo->costo_personalizado,
                'tiempo_personalizado_min' => $nodo->tiempo_personalizado_min,
                'es_punto_inicio' => $nodo->es_punto_inicio,
                'es_punto_final' => $nodo->es_punto_final,
                'notas' => $nodo->notas
            ]);

            // Mapear IDs antiguos a nuevos para las conexiones
            $mapeoNodos[$nodo->id] = $nuevoNodo->id;
        }

        // Copiar conexiones
        foreach ($this->conexiones as $conexion) {
            $nuevoFlujo->conexiones()->create([
                'id_nodo_origen' => $mapeoNodos[$conexion->id_nodo_origen],
                'id_nodo_destino' => $mapeoNodos[$conexion->id_nodo_destino],
                'tipo_conexion' => $conexion->tipo_conexion,
                'condicion_activacion' => $conexion->condicion_activacion,
                'etiqueta' => $conexion->etiqueta,
                'estilo_linea' => $conexion->estilo_linea,
                'color_linea' => $conexion->color_linea,
                'es_animada' => $conexion->es_animada,
                'orden_prioridad' => $conexion->orden_prioridad
            ]);
        }

        return $nuevoFlujo;
    }
}
