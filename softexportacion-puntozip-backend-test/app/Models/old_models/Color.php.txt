<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Color extends Model
{
    protected $table = 'colores';
    
    protected $fillable = [
        'nombre',
        'codigo_hex',
        'codigo_pantone',
        'estado'
    ];

    protected $casts = [
        'fecha_creacion' => 'datetime'
    ];

    const UPDATED_AT = null;
    const CREATED_AT = 'fecha_creacion';

    // Relaciones
    public function variantes(): HasMany
    {
        return $this->hasMany(VarianteEstilo::class, 'id_color');
    }

    public function materiales(): BelongsToMany
    {
        return $this->belongsToMany(Material::class, 'materiales_colores', 'id_color', 'id_material')
                    ->withPivot('costo_adicional', 'estado')
                    ->withTimestamps();
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }
}
