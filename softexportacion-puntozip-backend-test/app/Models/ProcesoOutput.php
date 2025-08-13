<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcesoOutput extends Model
{
    protected $table = 'procesos_outputs';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'id_proceso',
        'descripcion',
        'tipo_output',
        'es_principal',
        'orden'
    ];

    protected $casts = [
        'es_principal' => 'boolean',
        'orden' => 'integer'
    ];

    const UPDATED_AT = null;
    const CREATED_AT = null;

    // Constantes para tipos de output
    const TIPO_SEMIFINAL = 'semifinal';
    const TIPO_FINAL = 'final';
    const TIPO_SUBPRODUCTO = 'subproducto';
    const TIPO_DESPERDICIO = 'desperdicio';

    // ============================================================================
    // RELACIONES
    // ============================================================================

    public function proceso(): BelongsTo
    {
        return $this->belongsTo(Proceso::class, 'id_proceso');
    }

    // ============================================================================
    // SCOPES
    // ============================================================================

    public function scopePrincipales($query)
    {
        return $query->where('es_principal', true);
    }

    public function scopeSecundarios($query)
    {
        return $query->where('es_principal', false);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_output', $tipo);
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
            self::TIPO_SEMIFINAL,
            self::TIPO_FINAL,
            self::TIPO_SUBPRODUCTO,
            self::TIPO_DESPERDICIO
        ];
    }
}
