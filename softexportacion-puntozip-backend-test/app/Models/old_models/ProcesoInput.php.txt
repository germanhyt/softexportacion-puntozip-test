<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcesoInput extends Model
{
    protected $table = 'procesos_inputs';

    protected $fillable = [
        'id_proceso',
        'descripcion',
        'cantidad_estimada',
        'unidad_referencia'
    ];

    public $timestamps = false;

    public function proceso(): BelongsTo
    {
        return $this->belongsTo(Proceso::class, 'id_proceso');
    }
}
