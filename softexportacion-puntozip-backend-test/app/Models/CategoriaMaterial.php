<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaMaterial extends Model
{
    protected $table = 'categorias_materiales';
    
    protected $fillable = [
        'nombre',
        'descripcion',
        'estado'
    ];

    protected $casts = [
        'fecha_creacion' => 'datetime',
        'fecha_actualizacion' => 'datetime'
    ];

    const UPDATED_AT = 'fecha_actualizacion';
    const CREATED_AT = 'fecha_creacion';

    // Relaciones
    public function materiales(): HasMany
    {
        return $this->hasMany(Material::class, 'id_categoria');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }
}
