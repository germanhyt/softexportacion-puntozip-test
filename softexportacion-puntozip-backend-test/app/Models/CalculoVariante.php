<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalculoVariante extends Model
{
    protected $table = 'calculos_variantes';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'id_variante_estilo',
        'id_flujo_estilo',
        'costo_materiales',
        'costo_procesos',
        'costo_total',
        'tiempo_total_min',
        'version',
        'es_actual'
    ];

    protected $casts = [
        'costo_materiales' => 'decimal:4',
        'costo_procesos' => 'decimal:4',
        'costo_total' => 'decimal:4',
        'tiempo_total_min' => 'decimal:2',
        'version' => 'integer',
        'es_actual' => 'boolean',
        'fecha_calculo' => 'datetime'
    ];

    const UPDATED_AT = null;
    const CREATED_AT = 'fecha_calculo';

    // Relaciones
    public function varianteEstilo(): BelongsTo
    {
        return $this->belongsTo(VarianteEstilo::class, 'id_variante_estilo');
    }

    public function flujoEstilo(): BelongsTo
    {
        return $this->belongsTo(FlujoEstilo::class, 'id_flujo_estilo');
    }

    // Scopes
    public function scopeActuales($query)
    {
        return $query->where('es_actual', true);
    }

    public function scopePorVariante($query, $varianteId)
    {
        return $query->where('id_variante_estilo', $varianteId);
    }

    public function scopePorFlujo($query, $flujoId)
    {
        return $query->where('id_flujo_estilo', $flujoId);
    }

    // Métodos de utilidad
    public function marcarComoActual()
    {
        // Desmarcar otros cálculos de la misma variante
        static::where('id_variante_estilo', $this->id_variante_estilo)
            ->where('id', '!=', $this->id)
            ->update(['es_actual' => false]);
            
        $this->update(['es_actual' => true]);
        
        return $this;
    }

    public function getPorcentajeMaterialesAttribute()
    {
        if ($this->costo_total == 0) return 0;
        return round(($this->costo_materiales / $this->costo_total) * 100, 2);
    }

    public function getPorcentajeProcesosAttribute()
    {
        if ($this->costo_total == 0) return 0;
        return round(($this->costo_procesos / $this->costo_total) * 100, 2);
    }
}
