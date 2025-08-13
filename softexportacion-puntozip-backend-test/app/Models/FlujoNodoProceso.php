<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlujoNodoProceso extends Model
{
    protected $table = 'flujos_nodos_procesos';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'id_flujo_estilo',
        'id_proceso',
        'orden_secuencia',
        'pos_x',
        'pos_y',
        'ancho',
        'alto',
        'costo_personalizado',
        'tiempo_personalizado_min',
        'es_punto_inicio',
        'es_punto_final',
        'notas',
        'estado'
    ];

    protected $casts = [
        'orden_secuencia' => 'integer',
        'pos_x' => 'decimal:2',
        'pos_y' => 'decimal:2',
        'ancho' => 'decimal:2',
        'alto' => 'decimal:2',
        'costo_personalizado' => 'decimal:4',
        'tiempo_personalizado_min' => 'decimal:2',
        'es_punto_inicio' => 'boolean',
        'es_punto_final' => 'boolean',
        'fecha_creacion' => 'datetime'
    ];

    const UPDATED_AT = null;
    const CREATED_AT = 'fecha_creacion';

    // Constantes para estados
    const ESTADO_ACTIVO = 'activo';
    const ESTADO_INACTIVO = 'inactivo';

    // ============================================================================
    // RELACIONES
    // ============================================================================

    public function flujoEstilo(): BelongsTo
    {
        return $this->belongsTo(FlujoEstilo::class, 'id_flujo_estilo');
    }

    public function proceso(): BelongsTo
    {
        return $this->belongsTo(Proceso::class, 'id_proceso');
    }

    public function conexionesOrigen(): HasMany
    {
        return $this->hasMany(FlujoConexion::class, 'id_nodo_origen');
    }

    public function conexionesDestino(): HasMany
    {
        return $this->hasMany(FlujoConexion::class, 'id_nodo_destino');
    }

    // ============================================================================
    // SCOPES
    // ============================================================================

    public function scopeActivos($query)
    {
        return $query->where('estado', self::ESTADO_ACTIVO);
    }

    public function scopePuntosInicio($query)
    {
        return $query->where('es_punto_inicio', true);
    }

    public function scopePuntosFinal($query)
    {
        return $query->where('es_punto_final', true);
    }

    public function scopePorOrdenSecuencia($query)
    {
        return $query->orderBy('orden_secuencia', 'asc');
    }

    public function scopePorFlujo($query, $flujoId)
    {
        return $query->where('id_flujo_estilo', $flujoId);
    }

    // ============================================================================
    // MÉTODOS AUXILIARES
    // ============================================================================

    /**
     * Obtener costo efectivo del nodo (personalizado o base)
     */
    public function getCostoEfectivoAttribute()
    {
        return $this->costo_personalizado ?? $this->proceso->costo_base;
    }

    /**
     * Obtener tiempo efectivo del nodo (personalizado o base)
     */
    public function getTiempoEfectivoAttribute()
    {
        return $this->tiempo_personalizado_min ?? $this->proceso->tiempo_base_min;
    }

    /**
     * Obtener costo con merma aplicada
     */
    public function getCostoConMermaAttribute()
    {
        $costo = $this->costo_efectivo;
        if ($this->proceso->merma_porcentaje > 0) {
            $costo *= (1 + ($this->proceso->merma_porcentaje / 100));
        }
        return $costo;
    }

    /**
     * Obtener tiempo con merma aplicada
     */
    public function getTiempoConMermaAttribute()
    {
        $tiempo = $this->tiempo_efectivo;
        if ($this->proceso->merma_porcentaje > 0) {
            $tiempo *= (1 + ($this->proceso->merma_porcentaje / 100));
        }
        return $tiempo;
    }

    /**
     * Actualizar posición del nodo (para ReactFlow)
     */
    public function actualizarPosicion($x, $y)
    {
        $this->update([
            'pos_x' => $x,
            'pos_y' => $y
        ]);
    }

    /**
     * Actualizar dimensiones del nodo
     */
    public function actualizarDimensiones($ancho, $alto)
    {
        $this->update([
            'ancho' => $ancho,
            'alto' => $alto
        ]);
    }

    /**
     * Obtener nodos siguientes en el flujo
     */
    public function getNodosSiguientes()
    {
        return self::whereIn('id', 
            $this->conexionesOrigen()->pluck('id_nodo_destino')
        )->with('proceso')->get();
    }

    /**
     * Obtener nodos anteriores en el flujo
     */
    public function getNodosAnteriores()
    {
        return self::whereIn('id', 
            $this->conexionesDestino()->pluck('id_nodo_origen')
        )->with('proceso')->get();
    }

    /**
     * Verificar si el nodo puede moverse a una nueva posición
     */
    public function puedeMoverse($nuevaX, $nuevaY)
    {
        // Verificar límites del lienzo
        if ($nuevaX < 0 || $nuevaY < 0) {
            return false;
        }

        // Verificar solapamiento con otros nodos
        $nodosEnPosicion = self::where('id_flujo_estilo', $this->id_flujo_estilo)
            ->where('id', '!=', $this->id)
            ->where('pos_x', '>=', $nuevaX - $this->ancho/2)
            ->where('pos_x', '<=', $nuevaX + $this->ancho/2)
            ->where('pos_y', '>=', $nuevaY - $this->alto/2)
            ->where('pos_y', '<=', $nuevaY + $this->alto/2)
            ->exists();

        return !$nodosEnPosicion;
    }

    /**
     * Obtener información completa del nodo para ReactFlow
     */
    public function getInfoParaReactFlow()
    {
        return [
            'id' => (string)$this->id,
            'type' => 'customNode',
            'position' => [
                'x' => (float)$this->pos_x,
                'y' => (float)$this->pos_y
            ],
            'data' => [
                'id_nodo' => $this->id,
                'id_proceso' => $this->id_proceso,
                'codigo' => $this->proceso->codigo,
                'nombre' => $this->proceso->nombre,
                'descripcion' => $this->proceso->descripcion,
                'tipo' => $this->proceso->tipoProceso->nombre ?? 'Sin tipo',
                'costo_base' => $this->proceso->costo_base,
                'costo_personalizado' => $this->costo_personalizado,
                'costo_efectivo' => $this->costo_efectivo,
                'costo_con_merma' => $this->costo_con_merma,
                'tiempo_base_min' => $this->proceso->tiempo_base_min,
                'tiempo_personalizado_min' => $this->tiempo_personalizado_min,
                'tiempo_efectivo' => $this->tiempo_efectivo,
                'tiempo_con_merma' => $this->tiempo_con_merma,
                'merma_porcentaje' => $this->proceso->merma_porcentaje,
                'es_paralelo' => $this->proceso->es_paralelo,
                'es_opcional' => $this->proceso->es_opcional,
                'requiere_color' => $this->proceso->requiere_color,
                'es_punto_inicio' => $this->es_punto_inicio,
                'es_punto_final' => $this->es_punto_final,
                'orden_secuencia' => $this->orden_secuencia,
                'color_tipo' => $this->proceso->tipoProceso->color_hex ?? '#E5E7EB',
                'notas' => $this->notas,
                'ancho' => (float)$this->ancho,
                'alto' => (float)$this->alto,
                'inputs' => $this->proceso->inputs->pluck('descripcion')->toArray(),
                'outputs' => $this->proceso->outputs->pluck('descripcion')->toArray(),
                'tiene_personalizaciones' => !is_null($this->costo_personalizado) || !is_null($this->tiempo_personalizado_min)
            ],
            'style' => [
                'width' => $this->ancho,
                'height' => $this->alto,
                'border' => $this->es_punto_inicio || $this->es_punto_final ? '3px solid #059669' : '1px solid #D1D5DB',
                'borderRadius' => '8px',
                'background' => $this->proceso->tipoProceso->color_hex ?? '#F9FAFB'
            ]
        ];
    }

    /**
     * Obtener estadísticas del nodo
     */
    public function getEstadisticas()
    {
        return [
            'posicion' => [
                'x' => $this->pos_x,
                'y' => $this->pos_y
            ],
            'dimensiones' => [
                'ancho' => $this->ancho,
                'alto' => $this->alto
            ],
            'costos' => [
                'base' => $this->proceso->costo_base,
                'personalizado' => $this->costo_personalizado,
                'efectivo' => $this->costo_efectivo,
                'con_merma' => $this->costo_con_merma
            ],
            'tiempos' => [
                'base_min' => $this->proceso->tiempo_base_min,
                'personalizado_min' => $this->tiempo_personalizado_min,
                'efectivo_min' => $this->tiempo_efectivo,
                'con_merma_min' => $this->tiempo_con_merma
            ],
            'conexiones' => [
                'entrada' => $this->conexionesDestino->count(),
                'salida' => $this->conexionesOrigen->count()
            ],
            'caracteristicas' => [
                'es_punto_inicio' => $this->es_punto_inicio,
                'es_punto_final' => $this->es_punto_final,
                'es_paralelo' => $this->proceso->es_paralelo,
                'es_opcional' => $this->proceso->es_opcional,
                'requiere_color' => $this->proceso->requiere_color,
                'tiene_merma' => $this->proceso->merma_porcentaje > 0,
                'tiene_personalizaciones' => !is_null($this->costo_personalizado) || !is_null($this->tiempo_personalizado_min)
            ]
        ];
    }

    /**
     * Validar configuración del nodo
     */
    public function validarConfiguracion()
    {
        $errores = [];

        // Validar que un nodo no sea inicio y final a la vez
        if ($this->es_punto_inicio && $this->es_punto_final) {
            $errores[] = 'Un nodo no puede ser punto de inicio y final simultáneamente';
        }

        // Validar orden de secuencia
        if ($this->orden_secuencia < 1) {
            $errores[] = 'El orden de secuencia debe ser mayor a 0';
        }

        // Validar posición
        if ($this->pos_x < 0 || $this->pos_y < 0) {
            $errores[] = 'La posición del nodo no puede ser negativa';
        }

        // Validar dimensiones
        if ($this->ancho < 50 || $this->alto < 30) {
            $errores[] = 'Las dimensiones del nodo son demasiado pequeñas';
        }

        // Validar personalizaciones
        if ($this->costo_personalizado !== null && $this->costo_personalizado < 0) {
            $errores[] = 'El costo personalizado no puede ser negativo';
        }

        if ($this->tiempo_personalizado_min !== null && $this->tiempo_personalizado_min < 0) {
            $errores[] = 'El tiempo personalizado no puede ser negativo';
        }

        return [
            'es_valido' => empty($errores),
            'errores' => $errores
        ];
    }

    /**
     * Métodos estáticos para obtener opciones
     */
    public static function getEstados()
    {
        return [
            self::ESTADO_ACTIVO,
            self::ESTADO_INACTIVO
        ];
    }
}
