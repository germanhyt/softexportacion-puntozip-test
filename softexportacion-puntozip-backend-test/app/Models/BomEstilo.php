<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomEstilo extends Model
{
    protected $table = 'bom_estilos';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'id_estilo',
        'id_material',
        'cantidad_base',
        'id_proceso',
        'aplica_talla',
        'aplica_color',
        'es_critico',
        'estado'
    ];

    protected $casts = [
        'cantidad_base' => 'decimal:6',
        'aplica_talla' => 'boolean',
        'aplica_color' => 'boolean',
        'es_critico' => 'boolean',
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

    public function estilo(): BelongsTo
    {
        return $this->belongsTo(Estilo::class, 'id_estilo');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'id_material');
    }

    public function proceso(): BelongsTo
    {
        return $this->belongsTo(Proceso::class, 'id_proceso');
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

    public function scopeNoCriticos($query)
    {
        return $query->where('es_critico', false);
    }

    public function scopeQueAplicanTalla($query)
    {
        return $query->where('aplica_talla', true);
    }

    public function scopeQueAplicanColor($query)
    {
        return $query->where('aplica_color', true);
    }

    public function scopePorEstilo($query, $estiloId)
    {
        return $query->where('id_estilo', $estiloId);
    }

    public function scopePorMaterial($query, $materialId)
    {
        return $query->where('id_material', $materialId);
    }

    public function scopePorProceso($query, $procesoId)
    {
        return $query->where('id_proceso', $procesoId);
    }

    public function scopePorTipoMaterial($query, $tipo)
    {
        return $query->whereHas('material', function($q) use ($tipo) {
            $q->where('tipo_material', $tipo);
        });
    }

    // ============================================================================
    // MÉTODOS AUXILIARES
    // ============================================================================

    /**
     * Calcular cantidad final ajustada por talla
     */
    public function calcularCantidadFinal($multiplicadorTalla = 1.0)
    {
        if ($this->aplica_talla) {
            return $this->cantidad_base * $multiplicadorTalla;
        }
        
        return $this->cantidad_base;
    }

    /**
     * Calcular costo total del item del BOM
     */
    public function calcularCostoTotal($multiplicadorTalla = 1.0, $colorId = null)
    {
        $cantidadFinal = $this->calcularCantidadFinal($multiplicadorTalla);
        return $this->material->calcularCosto($cantidadFinal, $this->aplica_color ? $colorId : null);
    }

    /**
     * Verificar disponibilidad de stock
     */
    public function verificarDisponibilidadStock($multiplicadorTalla = 1.0)
    {
        $cantidadRequerida = $this->calcularCantidadFinal($multiplicadorTalla);
        return [
            'cantidad_requerida' => $cantidadRequerida,
            'stock_disponible' => $this->material->stock_actual,
            'es_suficiente' => $this->material->tieneStockSuficiente($cantidadRequerida),
            'faltante' => max(0, $cantidadRequerida - $this->material->stock_actual)
        ];
    }

    /**
     * Obtener información completa del item BOM
     */
    public function getInfoCompleta($multiplicadorTalla = 1.0, $colorId = null)
    {
        $cantidadFinal = $this->calcularCantidadFinal($multiplicadorTalla);
        $costoTotal = $this->calcularCostoTotal($multiplicadorTalla, $colorId);
        $disponibilidad = $this->verificarDisponibilidadStock($multiplicadorTalla);

        return [
            'bom_item' => [
                'id' => $this->id,
                'cantidad_base' => $this->cantidad_base,
                'cantidad_final' => $cantidadFinal,
                'aplica_talla' => $this->aplica_talla,
                'aplica_color' => $this->aplica_color,
                'es_critico' => $this->es_critico,
                'multiplicador_aplicado' => $multiplicadorTalla
            ],
            'material' => [
                'id' => $this->material->id,
                'codigo' => $this->material->codigo,
                'nombre' => $this->material->nombre,
                'tipo' => $this->material->tipo_material,
                'categoria' => $this->material->categoria->nombre ?? null,
                'unidad_medida' => $this->material->unidadMedida->codigo ?? null,
                'costo_unitario' => $this->material->costo_unitario,
                'proveedor' => $this->material->proveedor
            ],
            'proceso' => $this->proceso ? [
                'id' => $this->proceso->id,
                'codigo' => $this->proceso->codigo,
                'nombre' => $this->proceso->nombre,
                'tipo' => $this->proceso->tipoProceso->nombre ?? null
            ] : null,
            'costos' => [
                'costo_unitario' => $this->material->costo_unitario,
                'costo_total' => $costoTotal,
                'costo_por_unidad_final' => $cantidadFinal > 0 ? $costoTotal / $cantidadFinal : 0
            ],
            'disponibilidad' => $disponibilidad,
            'alertas' => $this->getAlertas($disponibilidad)
        ];
    }

    /**
     * Obtener alertas relacionadas con el item
     */
    private function getAlertas($disponibilidad)
    {
        $alertas = [];

        if (!$disponibilidad['es_suficiente']) {
            $alertas[] = [
                'tipo' => 'stock_insuficiente',
                'mensaje' => 'Stock insuficiente. Faltan ' . $disponibilidad['faltante'] . ' unidades.',
                'nivel' => 'error'
            ];
        }

        if ($this->es_critico && $this->material->stock_actual <= 10) {
            $alertas[] = [
                'tipo' => 'material_critico_stock_bajo',
                'mensaje' => 'Material crítico con stock bajo.',
                'nivel' => 'warning'
            ];
        }

        if ($this->material->estado === Material::ESTADO_INACTIVO) {
            $alertas[] = [
                'tipo' => 'material_inactivo',
                'mensaje' => 'El material está marcado como inactivo.',
                'nivel' => 'error'
            ];
        }

        if ($this->aplica_color && $this->material->tipo_material !== Material::TIPO_TINTE) {
            $alertas[] = [
                'tipo' => 'configuracion_color_incorrecta',
                'mensaje' => 'Item configurado para aplicar color pero el material no es de tipo tinte.',
                'nivel' => 'warning'
            ];
        }

        return $alertas;
    }

    /**
     * Calcular impacto del BOM en el costo total del estilo
     */
    public function getImpactoCosto($costoTotalEstilo)
    {
        if ($costoTotalEstilo <= 0) {
            return 0;
        }

        $costoItem = $this->calcularCostoTotal();
        return ($costoItem / $costoTotalEstilo) * 100;
    }

    /**
     * Obtener resumen para reportes
     */
    public function getResumenReporte($multiplicadorTalla = 1.0, $colorId = null)
    {
        $info = $this->getInfoCompleta($multiplicadorTalla, $colorId);
        
        return [
            'material_codigo' => $info['material']['codigo'],
            'material_nombre' => $info['material']['nombre'],
            'categoria' => $info['material']['categoria'],
            'tipo_material' => $info['material']['tipo'],
            'cantidad_requerida' => $info['bom_item']['cantidad_final'],
            'unidad_medida' => $info['material']['unidad_medida'],
            'costo_unitario' => $info['costos']['costo_unitario'],
            'costo_total' => $info['costos']['costo_total'],
            'proceso_asociado' => $info['proceso']['nombre'] ?? 'Ninguno',
            'es_critico' => $this->es_critico,
            'stock_disponible' => $info['disponibilidad']['stock_disponible'],
            'stock_suficiente' => $info['disponibilidad']['es_suficiente'],
            'alertas_count' => count($info['alertas'])
        ];
    }

    /**
     * Métodos estáticos para análisis
     */
    public static function getEstadisticasPorEstilo($estiloId)
    {
        $bomItems = self::porEstilo($estiloId)->activos()->with(['material', 'proceso'])->get();
        
        return [
            'total_items' => $bomItems->count(),
            'items_criticos' => $bomItems->where('es_critico', true)->count(),
            'items_con_talla' => $bomItems->where('aplica_talla', true)->count(),
            'items_con_color' => $bomItems->where('aplica_color', true)->count(),
            'costo_total_materiales' => $bomItems->sum(function($item) {
                return $item->calcularCostoTotal();
            }),
            'materiales_por_tipo' => $bomItems->groupBy('material.tipo_material')->map->count(),
            'procesos_involucrados' => $bomItems->whereNotNull('id_proceso')->pluck('proceso.nombre')->unique()->values()
        ];
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
