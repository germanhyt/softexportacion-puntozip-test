<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcesoInput extends Model
{
    protected $table = 'procesos_inputs';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'id_proceso',
        'descripcion',
        'tipo_input',
        'id_material',
        'id_proceso_origen',
        'es_obligatorio',
        'orden'
    ];

    protected $casts = [
        'es_obligatorio' => 'boolean',
        'orden' => 'integer'
    ];

    const UPDATED_AT = null;
    const CREATED_AT = null;

    // Constantes para tipos de input
    const TIPO_MATERIAL = 'material';
    const TIPO_SEMIFINAL = 'semifinal';
    const TIPO_OTRO = 'otro';

    // ============================================================================
    // RELACIONES
    // ============================================================================

    public function proceso(): BelongsTo
    {
        return $this->belongsTo(Proceso::class, 'id_proceso');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'id_material');
    }

    public function procesoOrigen(): BelongsTo
    {
        return $this->belongsTo(Proceso::class, 'id_proceso_origen');
    }

    // ============================================================================
    // SCOPES
    // ============================================================================

    public function scopeObligatorios($query)
    {
        return $query->where('es_obligatorio', true);
    }

    public function scopeOpcionales($query)
    {
        return $query->where('es_obligatorio', false);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_input', $tipo);
    }

    public function scopePorOrden($query)
    {
        return $query->orderBy('orden', 'asc');
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
            self::TIPO_MATERIAL,
            self::TIPO_SEMIFINAL,
            self::TIPO_OTRO
        ];
    }
}
