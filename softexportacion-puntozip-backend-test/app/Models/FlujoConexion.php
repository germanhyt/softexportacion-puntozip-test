<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlujoConexion extends Model
{
    protected $table = 'flujos_conexiones';
    
    protected $fillable = [
        'id_flujo_estilo',
        'id_nodo_origen',
        'id_nodo_destino',
        'tipo_conexion',
        'condicion_activacion',
        'etiqueta',
        'estilo_linea',
        'color_linea',
        'es_animada',
        'orden_prioridad',
        'estado'
    ];

    protected $casts = [
        'orden_prioridad' => 'integer',
        'es_animada' => 'boolean',
        'fecha_creacion' => 'datetime'
    ];

    const UPDATED_AT = null;
    const CREATED_AT = 'fecha_creacion';

    // Relaciones
    public function flujoEstilo(): BelongsTo
    {
        return $this->belongsTo(FlujoEstilo::class, 'id_flujo_estilo');
    }

    public function nodoOrigen(): BelongsTo
    {
        return $this->belongsTo(FlujoNodoProceso::class, 'id_nodo_origen');
    }

    public function nodoDestino(): BelongsTo
    {
        return $this->belongsTo(FlujoNodoProceso::class, 'id_nodo_destino');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_conexion', $tipo);
    }

    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden_prioridad');
    }

    // Validación
    public function esConexionValida(): bool
    {
        return $this->id_nodo_origen !== $this->id_nodo_destino;
    }
}
