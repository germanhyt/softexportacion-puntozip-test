<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomEstilo extends Model
{
    protected $table = 'bom_estilos';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'id_estilo',
        'id_material',
        'cantidad_base',
        'id_proceso',
        'es_critico',
        'estado'
    ];

    protected $casts = [
        'cantidad_base' => 'decimal:6',
        'es_critico' => 'boolean',
        'fecha_creacion' => 'datetime'
    ];

    const UPDATED_AT = null;
    const CREATED_AT = 'fecha_creacion';

    // Relaciones
    public function estilo(): BelongsTo
    {
        return $this->belongsTo(Estilo::class, 'id_estilo');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'id_material');
    }

    public function proceso(): BelongsTo
    {
        return $this->belongsTo(Proceso::class, 'id_proceso');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopeCriticos($query)
    {
        return $query->where('es_critico', true);
    }

    public function scopePorEstilo($query, $estiloId)
    {
        return $query->where('id_estilo', $estiloId);
    }

    // Métodos de cálculo
    public function calcularCantidadPorTalla($multiplicadorTalla = 1.0)
    {
        return $this->cantidad_base * $multiplicadorTalla;
    }

    public function calcularCostoPorTalla($multiplicadorTalla = 1.0)
    {
        $cantidad = $this->calcularCantidadPorTalla($multiplicadorTalla);
        return $cantidad * $this->material->costo_base;
    }

    


}
