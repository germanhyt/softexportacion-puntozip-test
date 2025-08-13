<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VarianteEstilo extends Model
{
    protected $table = 'variantes_estilos';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'id_estilo',
        'id_color',
        'id_talla',
        'codigo_sku',
        'costo_calculado',
        'tiempo_calculado_min',
        'estado'
    ];

    protected $casts = [
        'costo_calculado' => 'decimal:4',
        'tiempo_calculado_min' => 'decimal:2',
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

    public function estilo(): BelongsTo
    {
        return $this->belongsTo(Estilo::class, 'id_estilo');
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class, 'id_color');
    }

    public function talla(): BelongsTo
    {
        return $this->belongsTo(Talla::class, 'id_talla');
    }

    public function calculos(): HasMany
    {
        return $this->hasMany(CalculoVariante::class, 'id_variante_estilo');
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

    public function scopePorEstilo($query, $estiloId)
    {
        return $query->where('id_estilo', $estiloId);
    }

    public function scopePorColor($query, $colorId)
    {
        return $query->where('id_color', $colorId);
    }

    public function scopePorTalla($query, $tallaId)
    {
        return $query->where('id_talla', $tallaId);
    }

    // ============================================================================
    // MÉTODOS AUXILIARES
    // ============================================================================

    /**
     * Generar código SKU automáticamente
     */
    public function generarCodigoSku()
    {
        $codigoEstilo = $this->estilo->codigo ?? 'EST';
        $codigoColor = $this->color->codigo_hex ?? substr($this->color->nombre, 0, 3);
        $codigoTalla = $this->talla->codigo ?? 'M';
        
        return strtoupper($codigoEstilo . '-' . $codigoColor . '-' . $codigoTalla);
    }

    /**
     * Obtener multiplicador de talla
     */
    public function getMultiplicadorTalla()
    {
        return $this->talla->multiplicador_cantidad ?? 1.0;
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
