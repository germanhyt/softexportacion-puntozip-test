<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnidadMedida extends Model
{
    protected $table = 'unidades_medida';
    protected $primaryKey = 'id';
    
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

    // Constantes para tipos
    const TIPO_PESO = 'peso';
    const TIPO_LONGITUD = 'longitud';
    const TIPO_VOLUMEN = 'volumen';
    const TIPO_AREA = 'area';
    const TIPO_UNIDAD = 'unidad';

    // Constantes para estados
    const ESTADO_ACTIVO = 'activo';
    const ESTADO_INACTIVO = 'inactivo';

    // ============================================================================
    // RELACIONES
    // ============================================================================

    public function materiales(): HasMany
    {
        return $this->hasMany(Material::class, 'id_unidad_medida');
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

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    // ============================================================================
    // MÉTODOS AUXILIARES
    // ============================================================================

    /**
     * Métodos estáticos para obtener opciones
     */
    public static function getTipos()
    {
        return [
            self::TIPO_PESO,
            self::TIPO_LONGITUD,
            self::TIPO_VOLUMEN,
            self::TIPO_AREA,
            self::TIPO_UNIDAD
        ];
    }

    public static function getEstados()
    {
        return [
            self::ESTADO_ACTIVO,
            self::ESTADO_INACTIVO
        ];
    }

    /**
     * Obtener unidades disponibles para dropdown
     */
    public static function getUnidadesDisponibles()
    {
        return self::activas()
                   ->orderBy('nombre')
                   ->get()
                   ->mapWithKeys(function($unidad) {
                       return [$unidad->id => $unidad->nombre . ' (' . $unidad->codigo . ')'];
                   });
    }
}
