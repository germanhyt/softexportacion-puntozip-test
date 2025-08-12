<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnidadMedida extends Model
{
    protected $table = 'unidades_medida';
    
    protected $fillable = [
        'codigo',
        'nombre', 
        'tipo',
        'estado'
    ];

    protected $casts = [
        'fecha_creacion' => 'datetime'
    ];

    const UPDATED_AT = null;
    const CREATED_AT = 'fecha_creacion';

    // Relaciones
    public function materiales(): HasMany
    {
        return $this->hasMany(Material::class, 'id_unidad_medida');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }
}
