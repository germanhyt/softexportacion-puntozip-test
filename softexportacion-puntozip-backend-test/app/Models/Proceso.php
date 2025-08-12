<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Proceso extends Model
{
    protected $table = 'procesos';
    
    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'sop',
        'id_tipo_proceso',
        'costo_base',
        'tiempo_base_min',
        'merma_porcentaje',
        'es_paralelo',
        'estado'
    ];

    protected $casts = [
        'costo_base' => 'decimal:4',
        'tiempo_base_min' => 'decimal:2',
        'merma_porcentaje' => 'decimal:2',
        'es_paralelo' => 'boolean',
        'fecha_creacion' => 'datetime',
        'fecha_actualizacion' => 'datetime'
    ];

    protected $appends = [ 'costo_con_merma' ];

    const UPDATED_AT = 'fecha_actualizacion';
    const CREATED_AT = 'fecha_creacion';

    // Relaciones
    public function tipoProceso(): BelongsTo
    {
        return $this->belongsTo(TipoProceso::class, 'id_tipo_proceso');
    }

    public function nodosFlujos(): HasMany
    {
        return $this->hasMany(FlujoNodoProceso::class, 'id_proceso');
    }

    public function bomItems(): HasMany
    {
        return $this->hasMany(BomEstilo::class, 'id_proceso');
    }

    public function inputs(): HasMany
    {
        return $this->hasMany(ProcesoInput::class, 'id_proceso');
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(ProcesoOutput::class, 'id_proceso');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    // Ajustado: la tabla no tiene columna 'tipo', se filtra por id_tipo_proceso
    public function scopePorTipo($query, $idTipoProceso)
    {
        return $query->where('id_tipo_proceso', $idTipoProceso);
    }

    // Accessors calculados
    // Costo con merma: costo_base * (1 + merma_porcentaje/100)
    public function getCostoConMermaAttribute(): float
    {
        $costo = (float) ($this->costo_base ?? 0);
        $merma = (float) ($this->merma_porcentaje ?? 0);
        return round($costo * (1 + ($merma / 100)), 4);
    }
}
