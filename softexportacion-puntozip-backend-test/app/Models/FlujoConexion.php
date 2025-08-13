<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlujoConexion extends Model
{
    protected $table = 'flujos_conexiones';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'id_flujo_estilo',
        'id_nodo_origen',
        'id_nodo_destino',
        'tipo_conexion',
        'condicion_activacion',
        'etiqueta',
        'estilo_linea',
        'color_linea',
        'es_animada',
        'orden_prioridad',
        'estado'
    ];

    protected $casts = [
        'orden_prioridad' => 'integer',
        'es_animada' => 'boolean',
        'fecha_creacion' => 'datetime'
    ];

    const UPDATED_AT = null;
    const CREATED_AT = 'fecha_creacion';

    // Constantes para tipos de conexión
    const TIPO_SECUENCIAL = 'secuencial';
    const TIPO_CONDICIONAL = 'condicional';
    const TIPO_PARALELO = 'paralelo';

    // Constantes para estilos de línea
    const ESTILO_SOLIDA = 'solida';
    const ESTILO_PUNTEADA = 'punteada';
    const ESTILO_DISCONTINUA = 'discontinua';

    // Constantes para estados
    const ESTADO_ACTIVO = 'activo';
    const ESTADO_INACTIVO = 'inactivo';

    // ============================================================================
    // RELACIONES
    // ============================================================================

    public function flujoEstilo(): BelongsTo
    {
        return $this->belongsTo(FlujoEstilo::class, 'id_flujo_estilo');
    }

    public function nodoOrigen(): BelongsTo
    {
        return $this->belongsTo(FlujoNodoProceso::class, 'id_nodo_origen');
    }

    public function nodoDestino(): BelongsTo
    {
        return $this->belongsTo(FlujoNodoProceso::class, 'id_nodo_destino');
    }

    // ============================================================================
    // SCOPES
    // ============================================================================

    public function scopeActivas($query)
    {
        return $query->where('estado', self::ESTADO_ACTIVO);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_conexion', $tipo);
    }

    public function scopeAnimadas($query)
    {
        return $query->where('es_animada', true);
    }

    public function scopePorPrioridad($query)
    {
        return $query->orderBy('orden_prioridad', 'asc');
    }

    // ============================================================================
    // MÉTODOS AUXILIARES
    // ============================================================================

    /**
     * Obtener información para ReactFlow
     */
    public function getInfoParaReactFlow()
    {
        return [
            'id' => (string)$this->id,
            'source' => (string)$this->id_nodo_origen,
            'target' => (string)$this->id_nodo_destino,
            'type' => $this->getTipoReactFlow(),
            'data' => [
                'tipo_conexion' => $this->tipo_conexion,
                'etiqueta' => $this->etiqueta,
                'condicion_activacion' => $this->condicion_activacion,
                'orden_prioridad' => $this->orden_prioridad
            ],
            'style' => [
                'stroke' => $this->color_linea,
                'strokeWidth' => 2,
                'strokeDasharray' => $this->getEstiloLineaCSS()
            ],
            'animated' => $this->es_animada,
            'label' => $this->etiqueta,
            'labelStyle' => [
                'fontSize' => '12px',
                'fontWeight' => 'bold',
                'fill' => $this->color_linea
            ]
        ];
    }

    /**
     * Obtener tipo de conexión para ReactFlow
     */
    private function getTipoReactFlow()
    {
        switch ($this->tipo_conexion) {
            case self::TIPO_CONDICIONAL:
                return 'step';
            case self::TIPO_PARALELO:
                return 'straight';
            default:
                return 'smoothstep';
        }
    }

    /**
     * Obtener estilo de línea para CSS
     */
    private function getEstiloLineaCSS()
    {
        switch ($this->estilo_linea) {
            case self::ESTILO_PUNTEADA:
                return '5,5';
            case self::ESTILO_DISCONTINUA:
                return '10,5';
            default:
                return '0';
        }
    }

    /**
     * Validar que la conexión no cree un ciclo
     */
    public function validarNoCiclo()
    {
        // Implementar algoritmo de detección de ciclos
        // Por simplicidad, verificamos que no se conecte un nodo consigo mismo
        if ($this->id_nodo_origen === $this->id_nodo_destino) {
            return false;
        }

        // Verificar ciclos más complejos usando BFS
        return !$this->detectarCiclo($this->id_nodo_origen, $this->id_nodo_destino);
    }

    /**
     * Detectar ciclo usando BFS
     */
    private function detectarCiclo($nodoInicio, $nodoFin)
    {
        if ($nodoInicio === $nodoFin) {
            return true;
        }

        $visitados = [];
        $cola = [$nodoInicio];

        while (!empty($cola)) {
            $nodoActual = array_shift($cola);
            
            if (in_array($nodoActual, $visitados)) {
                continue;
            }

            $visitados[] = $nodoActual;

            // Obtener nodos conectados desde el actual
            $conexionesDesdeActual = self::where('id_nodo_origen', $nodoActual)
                ->where('id_flujo_estilo', $this->id_flujo_estilo)
                ->pluck('id_nodo_destino');

            foreach ($conexionesDesdeActual as $siguienteNodo) {
                if ($siguienteNodo === $nodoFin) {
                    return true; // Se encontró un camino de vuelta
                }
                
                if (!in_array($siguienteNodo, $visitados)) {
                    $cola[] = $siguienteNodo;
                }
            }
        }

        return false;
    }

    /**
     * Validar configuración de la conexión
     */
    public function validarConfiguracion()
    {
        $errores = [];

        // Validar que origen y destino sean diferentes
        if ($this->id_nodo_origen === $this->id_nodo_destino) {
            $errores[] = 'Un nodo no puede conectarse consigo mismo';
        }

        // Validar que no cree ciclos
        if (!$this->validarNoCiclo()) {
            $errores[] = 'La conexión crearía un ciclo en el flujo';
        }

        // Validar condición de activación para conexiones condicionales
        if ($this->tipo_conexion === self::TIPO_CONDICIONAL && empty($this->condicion_activacion)) {
            $errores[] = 'Las conexiones condicionales requieren una condición de activación';
        }

        // Validar que el color sea válido
        if ($this->color_linea && !preg_match('/^#[0-9A-Fa-f]{6}$/', $this->color_linea)) {
            $errores[] = 'El color de línea debe ser un código hexadecimal válido';
        }

        // Validar orden de prioridad
        if ($this->orden_prioridad < 1) {
            $errores[] = 'El orden de prioridad debe ser mayor a 0';
        }

        return [
            'es_valido' => empty($errores),
            'errores' => $errores
        ];
    }

    /**
     * Obtener estadísticas de la conexión
     */
    public function getEstadisticas()
    {
        return [
            'conexion' => [
                'id' => $this->id,
                'tipo' => $this->tipo_conexion,
                'etiqueta' => $this->etiqueta,
                'es_animada' => $this->es_animada,
                'orden_prioridad' => $this->orden_prioridad
            ],
            'nodos' => [
                'origen' => [
                    'id' => $this->nodoOrigen->id,
                    'proceso' => $this->nodoOrigen->proceso->nombre,
                    'orden_secuencia' => $this->nodoOrigen->orden_secuencia
                ],
                'destino' => [
                    'id' => $this->nodoDestino->id,
                    'proceso' => $this->nodoDestino->proceso->nombre,
                    'orden_secuencia' => $this->nodoDestino->orden_secuencia
                ]
            ],
            'estilo' => [
                'color' => $this->color_linea,
                'linea' => $this->estilo_linea
            ],
            'validacion' => $this->validarConfiguracion()
        ];
    }

    /**
     * Métodos estáticos para obtener opciones
     */
    public static function getTiposConexion()
    {
        return [
            self::TIPO_SECUENCIAL,
            self::TIPO_CONDICIONAL,
            self::TIPO_PARALELO
        ];
    }

    public static function getEstilosLinea()
    {
        return [
            self::ESTILO_SOLIDA,
            self::ESTILO_PUNTEADA,
            self::ESTILO_DISCONTINUA
        ];
    }

    public static function getEstados()
    {
        return [
            self::ESTADO_ACTIVO,
            self::ESTADO_INACTIVO
        ];
    }
}
