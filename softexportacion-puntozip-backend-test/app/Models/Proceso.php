<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proceso extends Model
{
    protected $table = 'procesos';
    protected $primaryKey = 'id';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'sop',
        'id_tipo_proceso',
        'costo_base',
        'tiempo_base_min',
        'merma_porcentaje',
        'es_paralelo',
        'es_opcional',
        'requiere_color',
        'estado'
    ];

    protected $casts = [
        'costo_base' => 'decimal:4',
        'tiempo_base_min' => 'decimal:2',
        'merma_porcentaje' => 'decimal:2',
        'es_paralelo' => 'boolean',
        'es_opcional' => 'boolean',
        'requiere_color' => 'boolean',
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

    public function tipoProceso(): BelongsTo
    {
        return $this->belongsTo(TipoProceso::class, 'id_tipo_proceso');
    }

    public function inputs(): HasMany
    {
        return $this->hasMany(ProcesoInput::class, 'id_proceso');
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(ProcesoOutput::class, 'id_proceso');
    }

    public function bomItems(): HasMany
    {
        return $this->hasMany(BomEstilo::class, 'id_proceso');
    }

    public function nodosFlujó(): HasMany
    {
        return $this->hasMany(FlujoNodoProceso::class, 'id_proceso');
    }

    // Procesos que tienen este proceso como origen en sus inputs
    public function procesosDestino(): HasMany
    {
        return $this->hasMany(ProcesoInput::class, 'id_proceso_origen');
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

    public function scopeParalelos($query)
    {
        return $query->where('es_paralelo', true);
    }

    public function scopeSecuenciales($query)
    {
        return $query->where('es_paralelo', false);
    }

    public function scopeOpcionales($query)
    {
        return $query->where('es_opcional', true);
    }

    public function scopeObligatorios($query)
    {
        return $query->where('es_opcional', false);
    }

    public function scopeQueRequierenColor($query)
    {
        return $query->where('requiere_color', true);
    }

    public function scopePorTipo($query, $tipoId)
    {
        return $query->where('id_tipo_proceso', $tipoId);
    }

    public function scopeBuscar($query, $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('codigo', 'like', '%' . $termino . '%')
                ->orWhere('nombre', 'like', '%' . $termino . '%')
                ->orWhere('descripcion', 'like', '%' . $termino . '%');
        });
    }

    // ============================================================================
    // MÉTODOS AUXILIARES
    // ============================================================================

    /**
     * Calcular costo ajustado con merma
     */
    public function calcularCostoConMerma($costoBase = null)
    {
        $costo = $costoBase ?? $this->costo_base;
        return $costo * (1 + ($this->merma_porcentaje / 100));
    }

    /**
     * Calcular tiempo ajustado con merma
     */
    public function calcularTiempoConMerma($tiempoBase = null)
    {
        $tiempo = $tiempoBase ?? $this->tiempo_base_min;
        return $tiempo * (1 + ($this->merma_porcentaje / 100));
    }

    /**
     * Obtener información completa del proceso
     */
    public function getInfoCompleta()
    {
        return [
            'proceso' => [
                'id' => $this->id,
                'codigo' => $this->codigo,
                'nombre' => $this->nombre,
                'descripcion' => $this->descripcion,
                'tipo' => $this->tipoProceso->nombre ?? 'Sin tipo',
                'costo_base' => $this->costo_base,
                'tiempo_base_min' => $this->tiempo_base_min,
                'merma_porcentaje' => $this->merma_porcentaje,
                'es_paralelo' => $this->es_paralelo,
                'es_opcional' => $this->es_opcional,
                'requiere_color' => $this->requiere_color,
                'estado' => $this->estado
            ],
            'inputs' => $this->inputs->map(function ($input) {
                return [
                    'descripcion' => $input->descripcion,
                    'tipo' => $input->tipo_input,
                    'material' => $input->material->nombre ?? null,
                    'proceso_origen' => $input->procesoOrigen->nombre ?? null,
                    'es_obligatorio' => $input->es_obligatorio
                ];
            }),
            'outputs' => $this->outputs->map(function ($output) {
                return [
                    'descripcion' => $output->descripcion,
                    'tipo' => $output->tipo_output,
                    'es_principal' => $output->es_principal
                ];
            }),
            'estadisticas' => [
                'total_inputs' => $this->inputs->count(),
                'inputs_obligatorios' => $this->inputs->where('es_obligatorio', true)->count(),
                'total_outputs' => $this->outputs->count(),
                'output_principal' => $this->outputs->where('es_principal', true)->first()->descripcion ?? null,
                'uso_en_estilos' => $this->bomItems->pluck('id_estilo')->unique()->count(),
                'uso_en_flujos' => $this->nodosFlujó->pluck('id_flujo_estilo')->unique()->count()
            ]
        ];
    }

    /**
     * Verificar si el proceso puede ejecutarse en paralelo con otro
     */
    public function puedeEjecutarseEnParalelocon($otroProceso)
    {
        if (!$this->es_paralelo || !$otroProceso->es_paralelo) {
            return false;
        }

        // Verificar si hay dependencias entre procesos
        $hayDependencia = $this->inputs()->where('id_proceso_origen', $otroProceso->id)->exists() ||
            $otroProceso->inputs()->where('id_proceso_origen', $this->id)->exists();

        return !$hayDependencia;
    }

    /**
     * Obtener procesos que pueden ejecutarse después de este
     */
    public function getProcesosSiguientes()
    {
        return Proceso::whereHas('inputs', function ($query) {
            $query->where('id_proceso_origen', $this->id);
        })->activos()->get();
    }

    /**
     * Obtener procesos que deben ejecutarse antes de este
     */
    public function getProcesosAnteriores()
    {
        return Proceso::whereIn(
            'id',
            $this->inputs()->whereNotNull('id_proceso_origen')
                ->pluck('id_proceso_origen')
        )->activos()->get();
    }

    /**
     * Obtener SOP (Standard Operating Procedure) formateado
     */
    public function getSopFormateado()
    {
        if (!$this->sop) {
            return 'SOP no definido para este proceso';
        }

        return [
            'proceso' => $this->nombre,
            'codigo' => $this->codigo,
            'sop' => $this->sop,
            'inputs_requeridos' => $this->inputs->where('es_obligatorio', true)->pluck('descripcion'),
            'outputs_esperados' => $this->outputs->pluck('descripcion'),
            'tiempo_estimado' => $this->tiempo_base_min . ' minutos',
            'costo_estimado' => '$' . number_format($this->costo_base, 4),
            'consideraciones' => [
                'Merma estimada: ' . $this->merma_porcentaje . '%',
                'Proceso ' . ($this->es_paralelo ? 'PARALELO' : 'SECUENCIAL'),
                'Proceso ' . ($this->es_opcional ? 'OPCIONAL' : 'OBLIGATORIO'),
                $this->requiere_color ? 'REQUIERE especificación de color' : 'No requiere especificación de color'
            ]
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

    /**
     * Obtener procesos por tipo específico
     */
    public static function porTipoNombre($nombreTipo)
    {
        return self::whereHas('tipoProceso', function ($query) use ($nombreTipo) {
            $query->where('nombre', $nombreTipo);
        })->activos()->get();
    }

    /**
     * Formatear información para reactflow
     */
    public function getFormatoReactFlow()
    {
        return [
            'id' => (string)$this->id,
            'type' => 'customNode',
            'data' => [
                'codigo' => $this->codigo,
                'nombre' => $this->nombre,
                'descripcion' => $this->descripcion,
                'tipo' => $this->tipoProceso->nombre ?? 'Sin tipo',
                'costo_base' => $this->costo_base,
                'tiempo_base_min' => $this->tiempo_base_min,
                'es_paralelo' => $this->es_paralelo,
                'es_opcional' => $this->es_opcional,
                'requiere_color' => $this->requiere_color,
                'color_tipo' => $this->tipoProceso->color_hex ?? '#E5E7EB',
                'inputs' => $this->inputs->pluck('descripcion')->toArray(),
                'outputs' => $this->outputs->pluck('descripcion')->toArray()
            ]
        ];
    }
}
