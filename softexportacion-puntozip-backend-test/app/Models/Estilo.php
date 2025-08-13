<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Estilo extends Model
{
    protected $table = 'estilos';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'temporada',
        'año_produccion',
        'costo_objetivo',
        'tiempo_objetivo_min',
        'tipo_producto',
        'estado'
    ];

    protected $casts = [
        'costo_objetivo' => 'decimal:4',
        'tiempo_objetivo_min' => 'decimal:2',
        'año_produccion' => 'integer',
        'fecha_creacion' => 'datetime',
        'fecha_actualizacion' => 'datetime'
    ];

    const UPDATED_AT = 'fecha_actualizacion';
    const CREATED_AT = 'fecha_creacion';

    // Constantes para tipos de producto
    const TIPO_POLO = 'polo';
    const TIPO_CAMISA = 'camisa';
    const TIPO_PANTALON = 'pantalon';
    const TIPO_VESTIDO = 'vestido';
    const TIPO_OTRO = 'otro';

    // Constantes para temporadas
    const TEMPORADAS = ['primavera', 'verano', 'otoño', 'invierno'];

    // Constantes para estados
    const ESTADO_DESARROLLO = 'desarrollo';
    const ESTADO_ACTIVO = 'activo';
    const ESTADO_DESCONTINUADO = 'descontinuado';

    // ============================================================================
    // RELACIONES
    // ============================================================================

    public function variantes(): HasMany
    {
        return $this->hasMany(VarianteEstilo::class, 'id_estilo');
    }

    public function flujos(): HasMany
    {
        return $this->hasMany(FlujoEstilo::class, 'id_estilo');
    }

    public function bomItems(): HasMany
    {
        return $this->hasMany(BomEstilo::class, 'id_estilo');
    }

    // Relación con materiales a través del BOM
    public function materiales(): HasManyThrough
    {
        return $this->hasManyThrough(
            Material::class,
            BomEstilo::class,
            'id_estilo', // Foreign key en bom_estilos
            'id',        // Foreign key en materiales
            'id',        // Local key en estilos
            'id_material' // Local key en bom_estilos
        );
    }

    // Flujo actual (el que está marcado como es_actual = true)
    public function flujoActual()
    {
        return $this->hasOne(FlujoEstilo::class, 'id_estilo')->where('es_actual', true);
    }

    // ============================================================================
    // SCOPES
    // ============================================================================

    public function scopeActivos($query)
    {
        return $query->where('estado', self::ESTADO_ACTIVO);
    }

    public function scopeEnDesarrollo($query)
    {
        return $query->where('estado', self::ESTADO_DESARROLLO);
    }

    public function scopeDescontinuados($query)
    {
        return $query->where('estado', self::ESTADO_DESCONTINUADO);
    }

    public function scopePorTemporada($query, $temporada)
    {
        return $query->where('temporada', $temporada);
    }

    public function scopePorAño($query, $año)
    {
        return $query->where('año_produccion', $año);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_producto', $tipo);
    }

    public function scopeConVariantes($query)
    {
        return $query->whereHas('variantes');
    }

    public function scopeConFlujos($query)
    {
        return $query->whereHas('flujos');
    }

    // ============================================================================
    // MÉTODOS AUXILIARES
    // ============================================================================

    /**
     * Obtener el costo total calculado del estilo
     */
    public function getCostoCalculadoAttribute()
    {
        $flujoActual = $this->flujoActual;
        return $flujoActual ? $flujoActual->costo_total_calculado : 0;
    }

    /**
     * Obtener el tiempo total calculado del estilo
     */
    public function getTiempoCalculadoAttribute()
    {
        $flujoActual = $this->flujoActual;
        return $flujoActual ? $flujoActual->tiempo_total_calculado : 0;
    }

    /**
     * Verificar si el estilo cumple con el objetivo de costo
     */
    public function cumpleObjetivoCosto()
    {
        if (!$this->costo_objetivo) return null;
        return $this->costo_calculado <= $this->costo_objetivo;
    }

    /**
     * Verificar si el estilo cumple con el objetivo de tiempo
     */
    public function cumpleObjetivoTiempo()
    {
        if (!$this->tiempo_objetivo_min) return null;
        return $this->tiempo_calculado <= $this->tiempo_objetivo_min;
    }

    /**
     * Obtener estadísticas básicas del estilo
     */
    public function getEstadisticas()
    {
        return [
            'total_variantes' => $this->variantes()->count(),
            'variantes_activas' => $this->variantes()->where('estado', 'activo')->count(),
            'total_flujos' => $this->flujos()->count(),
            'total_materiales' => $this->bomItems()->count(),
            'materiales_criticos' => $this->bomItems()->where('es_critico', true)->count(),
            'costo_materiales' => $this->bomItems()->with('material')->get()->sum(function($item) {
                return $item->cantidad_base * $item->material->costo_unitario;
            }),
            'cumple_costo' => $this->cumpleObjetivoCosto(),
            'cumple_tiempo' => $this->cumpleObjetivoTiempo()
        ];
    }

    /**
     * Métodos estáticos para obtener opciones
     */
    public static function getTiposProducto()
    {
        return [
            self::TIPO_POLO,
            self::TIPO_CAMISA,
            self::TIPO_PANTALON,
            self::TIPO_VESTIDO,
            self::TIPO_OTRO
        ];
    }

    public static function getTemporadas()
    {
        return self::TEMPORADAS;
    }

    public static function getEstados()
    {
        return [
            self::ESTADO_DESARROLLO,
            self::ESTADO_ACTIVO,
            self::ESTADO_DESCONTINUADO
        ];
    }
}
