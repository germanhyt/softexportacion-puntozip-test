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

    // Relaciones
    public function procesos(): HasMany
    {
        return $this->hasMany(Proceso::class, 'id_tipo_proceso');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    // Métodos de utilidad
    public function getTotalProcesosAttribute()
    {
        return $this->procesos()->count();
    }

    public function getProcesosActivosAttribute()
    {
        return $this->procesos()->where('estado', 'activo')->count();
    }
}
