<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoProceso extends Model
{
    protected $table = 'tipos_procesos';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'nombre',
        'descripcion',
        'color_hex',
        'icono',
        'estado'
    ];

    protected $casts = [
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

    public function procesos(): HasMany
    {
        return $this->hasMany(Proceso::class, 'id_tipo_proceso');
    }

    // ============================================================================
    // SCOPES
    // ============================================================================

    public function scopeActivos($query)
    {
        return $query->where('estado', self::ESTADO_ACTIVO);
    }

    public function scopeInactivos($query)
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
     * Obtener tipos disponibles para dropdown
     */
    public static function getTiposDisponibles()
    {
        return self::activos()
                   ->orderBy('nombre')
                   ->get()
                   ->mapWithKeys(function($tipo) {
                       return [$tipo->id => $tipo->nombre];
                   });
    }
}
