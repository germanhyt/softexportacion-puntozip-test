<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VarianteEstilo extends Model
{
    protected $table = 'variantes_estilos';
    
    protected $fillable = [
        'id_estilo',
        'id_color',
        'talla',
        'precio_variante',
        'costo_adicional',
        'stock_disponible',
        'imagen_url',
        'estado'
    ];

    protected $casts = [
        'precio_variante' => 'decimal:2',
        'costo_adicional' => 'decimal:2',
        'stock_disponible' => 'integer',
        'fecha_creacion' => 'datetime'
    ];

    const UPDATED_AT = null;
    const CREATED_AT = 'fecha_creacion';

    // Relaciones
    public function estilo(): BelongsTo
    {
        return $this->belongsTo(Estilo::class, 'id_estilo');
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class, 'id_color');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopePorTalla($query, $talla)
    {
        return $query->where('talla', $talla);
    }

    public function scopeConStock($query)
    {
        return $query->where('stock_disponible', '>', 0);
    }

    // Calculados
    public function getPrecioTotalAttribute()
    {
        return $this->precio_variante + $this->costo_adicional;
    }
}
