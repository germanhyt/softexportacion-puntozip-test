<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Material extends Model
{
    protected $table = 'materiales';
    
    protected $fillable = [
        'codigo',
        'nombre',
        'id_categoria',
        'id_unidad_medida',
        'costo_unitario',
        'stock_actual',
        'proveedor',
        'estado'
    ];

    protected $casts = [
        'costo_unitario' => 'decimal:4',
        'stock_actual' => 'decimal:4',
        'fecha_creacion' => 'datetime',
        'fecha_actualizacion' => 'datetime'
    ];

    const UPDATED_AT = 'fecha_actualizacion';
    const CREATED_AT = 'fecha_creacion';

    // Relaciones
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaMaterial::class, 'id_categoria');
    }

    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class, 'id_unidad_medida');
    }

    public function colores(): BelongsToMany
    {
        return $this->belongsToMany(Color::class, 'materiales_colores', 'id_material', 'id_color')
                    ->withPivot('costo_adicional', 'estado')
                    ->withTimestamps();
    }

    public function recetas(): HasMany
    {
        return $this->hasMany(RecetaMaterial::class, 'id_material');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopePorCategoria($query, $categoriaId)
    {
        return $query->where('id_categoria', $categoriaId);
    }

    public function scopeConStock($query)
    {
        return $query->whereRaw('stock_minimo > 0');
    }
}
