<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecetaMaterial extends Model
{
    protected $table = 'recetas_materiales';
    
    protected $fillable = [
        'id_estilo',
        'id_material',
        'cantidad_necesaria',
        'unidad_medida_receta',
        'costo_unitario',
        'notas',
        'estado'
    ];

    protected $casts = [
        'cantidad_necesaria' => 'decimal:2',
        'costo_unitario' => 'decimal:2',
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

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopePorEstilo($query, $estiloId)
    {
        return $query->where('id_estilo', $estiloId);
    }

    // Calculados
    public function getCostoTotalAttribute()
    {
        return $this->cantidad_necesaria * $this->costo_unitario;
    }
}
