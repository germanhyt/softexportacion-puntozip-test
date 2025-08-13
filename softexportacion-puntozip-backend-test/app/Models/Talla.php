<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Talla extends Model
{
    protected $table = 'tallas';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'codigo',
        'nombre',
        'multiplicador_cantidad',
        'orden',
        'estado'
    ];

    protected $casts = [
        'multiplicador_cantidad' => 'decimal:3',
        'orden' => 'integer',
        'fecha_creacion' => 'datetime'
    ];

    const UPDATED_AT = null;
    const CREATED_AT = 'fecha_creacion';

    // Constantes para estados
    const ESTADO_ACTIVO = 'activo';
    const ESTADO_INACTIVO = 'inactivo';

    // ============================================================================
    // RELACIONES
    // ============================================================================

    public function variantes(): HasMany
    {
        return $this->hasMany(VarianteEstilo::class, 'id_talla');
    }

    // ============================================================================
    // SCOPES
    // ============================================================================

    public function scopeActivas($query)
    {
        return $query->where('estado', self::ESTADO_ACTIVO);
    }

    public function scopeInactivas($query)
    {
        return $query->where('estado', self::ESTADO_INACTIVO);
    }

    public function scopePorOrden($query)
    {
        return $query->orderBy('orden', 'asc');
    }

    // ============================================================================
    // MÉTODOS AUXILIARES
    // ============================================================================

    /**
     * Obtener tallas disponibles para dropdown
     */
    public static function getTallasDisponibles()
    {
        return self::activas()
                   ->porOrden()
                   ->get()
                   ->mapWithKeys(function($talla) {
                       return [$talla->id => $talla->nombre];
                   });
    }

    /**
     * Obtener multiplicador de cantidad para cálculos
     */
    public function getMultiplicadorAttribute()
    {
        return $this->multiplicador_cantidad;
    }

    /**
     * Métodos estáticos para obtener opciones
     */
    public static function getEstados()
    {
        return [
            self::ESTADO_ACTIVO,
            self::ESTADO_INACTIVO
        ];
    }
}
