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
        'costo_total_calculado' => 'decimal:4',
        'tiempo_total_calculado' => 'decimal:2',
        'version' => 'integer',
        'es_actual' => 'boolean',
        'fecha_creacion' => 'datetime',
        'fecha_actualizacion' => 'datetime'
    ];

    const UPDATED_AT = 'fecha_actualizacion';
    const CREATED_AT = 'fecha_creacion';

    // Relaciones
    public function estilo(): BelongsTo
    {
        return $this->belongsTo(Estilo::class, 'id_estilo');
    }

    public function nodos(): HasMany
    {
        return $this->hasMany(FlujoNodoProceso::class, 'id_flujo_estilo');
    }

    public function conexiones(): HasMany
    {
        return $this->hasMany(FlujoConexion::class, 'id_flujo_estilo');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopePorVersion($query, $version)
    {
        return $query->where('version', $version);
    }

    // Métodos de utilidad
    public function esVersionActiva()
    {
        return $this->estado === 'activo';
    }
}
