<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaMaterial extends Model
{
    protected $table = 'categorias_materiales';
    protected $primaryKey = 'id';
    
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

    // Constantes para estados
    const ESTADO_ACTIVO = 'activo';
    const ESTADO_INACTIVO = 'inactivo';

    // ============================================================================
    // RELACIONES
    // ============================================================================

    public function materiales(): HasMany
    {
        return $this->hasMany(Material::class, 'id_categoria');
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

    // ============================================================================
    // MÉTODOS AUXILIARES
    // ============================================================================

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

    /**
     * Obtener categorías disponibles para dropdown
     */
    public static function getCategoriasDisponibles()
    {
        return self::activas()
                   ->orderBy('nombre')
                   ->get()
                   ->mapWithKeys(function($categoria) {
                       return [$categoria->id => $categoria->nombre];
                   });
    }
}
