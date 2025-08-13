<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Material extends Model
{
    protected $table = 'materiales';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'codigo',
        'nombre',
        'id_categoria',
        'id_unidad_medida',
        'costo_unitario',
        'stock_actual',
        'proveedor',
        'tipo_material',
        'es_critico',
        'estado'
    ];

    protected $casts = [
        'costo_unitario' => 'decimal:4',
        'stock_actual' => 'decimal:4',
        'es_critico' => 'boolean',
        'fecha_creacion' => 'datetime',
        'fecha_actualizacion' => 'datetime'
    ];

    const UPDATED_AT = 'fecha_actualizacion';
    const CREATED_AT = 'fecha_creacion';

    // Constantes para tipos de materiales textiles
    const TIPO_HILO = 'hilo';
    const TIPO_TINTE = 'tinte';
    const TIPO_QUIMICO = 'quimico';
    const TIPO_TINTA = 'tinta';
    const TIPO_AVIO = 'avio';
    const TIPO_EMPAQUE = 'empaque';

    // Constantes para estados
    const ESTADO_ACTIVO = 'activo';
    const ESTADO_INACTIVO = 'inactivo';

    // ============================================================================
    // RELACIONES
    // ============================================================================

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaMaterial::class, 'id_categoria');
    }

    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class, 'id_unidad_medida');
    }

    public function bomItems(): HasMany
    {
        return $this->hasMany(BomEstilo::class, 'id_material');
    }

    public function colores(): BelongsToMany
    {
        return $this->belongsToMany(Color::class, 'materiales_colores', 'id_material', 'id_color')
                    ->withPivot('costo_adicional', 'estado', 'fecha_creacion');
    }

    public function procesoInputs(): HasMany
    {
        return $this->hasMany(ProcesoInput::class, 'id_material');
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

    public function scopeCriticos($query)
    {
        return $query->where('es_critico', true);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_material', $tipo);
    }

    public function scopePorCategoria($query, $categoriaId)
    {
        return $query->where('id_categoria', $categoriaId);
    }

    public function scopeConStock($query)
    {
        return $query->where('stock_actual', '>', 0);
    }

    public function scopeSinStock($query)
    {
        return $query->where('stock_actual', '<=', 0);
    }

    public function scopePorProveedor($query, $proveedor)
    {
        return $query->where('proveedor', 'like', '%' . $proveedor . '%');
    }

    public function scopeBuscar($query, $termino)
    {
        return $query->where(function($q) use ($termino) {
            $q->where('codigo', 'like', '%' . $termino . '%')
              ->orWhere('nombre', 'like', '%' . $termino . '%')
              ->orWhere('proveedor', 'like', '%' . $termino . '%');
        });
    }

    // ============================================================================
    // MÉTODOS AUXILIARES
    // ============================================================================

    /**
     * Verificar si el material tiene stock suficiente
     */
    public function tieneStockSuficiente($cantidadRequerida)
    {
        return $this->stock_actual >= $cantidadRequerida;
    }

    /**
     * Calcular el costo total para una cantidad específica
     */
    public function calcularCosto($cantidad, $colorId = null)
    {
        $costoBase = $this->costo_unitario * $cantidad;
        
        // Si es un material que maneja colores específicos (como tintes)
        if ($colorId && $this->tipo_material === self::TIPO_TINTE) {
            $materialColor = $this->colores()->where('id_color', $colorId)->first();
            if ($materialColor && $materialColor->pivot->costo_adicional) {
                $costoBase += $materialColor->pivot->costo_adicional * $cantidad;
            }
        }

        return $costoBase;
    }

    /**
     * Obtener información de consumo del material
     */
    public function getInfoConsumo()
    {
        return [
            'total_estilos_usado' => $this->bomItems()->distinct('id_estilo')->count(),
            'cantidad_total_consumida' => $this->bomItems()->sum('cantidad_base'),
            'valor_total_inventario' => $this->stock_actual * $this->costo_unitario,
            'es_critico' => $this->es_critico,
            'requiere_restock' => $this->stock_actual <= 10, // Umbral configurable
        ];
    }

    /**
     * Obtener materiales por tipo
     */
    public static function porTipoMaterial($tipo)
    {
        return self::where('tipo_material', $tipo)
                   ->activos()
                   ->with(['categoria', 'unidadMedida'])
                   ->orderBy('nombre')
                   ->get();
    }

    /**
     * Obtener materiales críticos con stock bajo
     */
    public static function criticosConStockBajo($umbral = 10)
    {
        return self::criticos()
                   ->activos()
                   ->where('stock_actual', '<=', $umbral)
                   ->with(['categoria', 'unidadMedida'])
                   ->orderBy('stock_actual')
                   ->get();
    }

    /**
     * Métodos estáticos para obtener opciones
     */
    public static function getTiposMaterial()
    {
        return [
            self::TIPO_HILO,
            self::TIPO_TINTE,
            self::TIPO_QUIMICO,
            self::TIPO_TINTA,
            self::TIPO_AVIO,
            self::TIPO_EMPAQUE
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
     * Formatear información para mostrar
     */
    public function getDescripcionCompletaAttribute()
    {
        $descripcion = $this->nombre;
        if ($this->categoria) {
            $descripcion .= ' (' . $this->categoria->nombre . ')';
        }
        if ($this->unidadMedida) {
            $descripcion .= ' - ' . $this->unidadMedida->codigo;
        }
        return $descripcion;
    }

    /**
     * Formatear costo con moneda
     */
    public function getCostoFormateadoAttribute()
    {
        return '$' . number_format($this->costo_unitario, 4);
    }

    /**
     * Obtener estado de stock
     */
    public function getEstadoStockAttribute()
    {
        if ($this->stock_actual <= 0) {
            return 'sin_stock';
        } elseif ($this->stock_actual <= 10) {
            return 'stock_bajo';
        } elseif ($this->stock_actual <= 50) {
            return 'stock_medio';
        } else {
            return 'stock_alto';
        }
    }
}
