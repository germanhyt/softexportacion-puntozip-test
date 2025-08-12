<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlujoNodoProceso extends Model
{
    protected $table = 'flujos_nodos_procesos';
    
    protected $fillable = [
        'id_flujo_estilo',
        'id_proceso',
        'pos_x',
        'pos_y',
        'ancho',
        'alto',
        'orden_secuencia',
        'tiempo_personalizado_min',
        'costo_personalizado',
        'es_opcional',
        'es_punto_inicio',
        'es_punto_final',
        'notas',
        'estado'
    ];

    protected $casts = [
        'pos_x' => 'decimal:2',
        'pos_y' => 'decimal:2',
        'ancho' => 'decimal:2',
        'alto' => 'decimal:2',
        'orden_secuencia' => 'integer',
        'tiempo_personalizado_min' => 'decimal:2',
        'costo_personalizado' => 'decimal:4',
        'es_opcional' => 'boolean',
        'es_punto_inicio' => 'boolean',
        'es_punto_final' => 'boolean',
        'fecha_creacion' => 'datetime'
    ];

    const UPDATED_AT = null;
    const CREATED_AT = 'fecha_creacion';

    // Relaciones
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

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden_secuencia');
    }

    // Accessors calculados
    public function getTiempoEfectivoAttribute(): float
    {
        $base = (float) ($this->tiempo_personalizado_min ?? $this->proceso->tiempo_base_min ?? 0);
        return $base; // ya está en minutos
    }

    public function getCostoEfectivoAttribute(): float
    {
        $costoBase = (float) ($this->costo_personalizado ?? $this->proceso->costo_base ?? 0);
        return $costoBase;
    }
}
