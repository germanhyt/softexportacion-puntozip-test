<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estilo;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class EstiloController extends Controller
{
    /**
     * Listar estilos con filtros y paginación
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Estilo::with(['variantes.color', 'variantes.talla', 'flujoActual']);

            // Aplicar filtros
            if ($request->has('temporada') && $request->temporada !== 'todas') {
                $query->porTemporada($request->temporada);
            }

            if ($request->has('año_produccion') && $request->año_produccion) {
                $query->porAño($request->año_produccion);
            }

            if ($request->has('tipo_producto') && $request->tipo_producto !== 'todos') {
                $query->porTipo($request->tipo_producto);
            }

            if ($request->has('estado') && $request->estado !== 'todos') {
                switch ($request->estado) {
                    case 'activos':
                        $query->activos();
                        break;
                    case 'desarrollo':
                        $query->enDesarrollo();
                        break;
                    case 'descontinuados':
                        $query->descontinuados();
                        break;
                }
            }

            if ($request->has('buscar') && $request->buscar) {
                $termino = $request->buscar;
                $query->where(function($q) use ($termino) {
                    $q->where('codigo', 'like', '%' . $termino . '%')
                      ->orWhere('nombre', 'like', '%' . $termino . '%')
                      ->orWhere('descripcion', 'like', '%' . $termino . '%');
                });
            }

            // Ordenamiento
            $ordenPor = $request->get('orden_por', 'nombre');
            $direccion = $request->get('direccion', 'asc');
            
            $query->orderBy($ordenPor, $direccion);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $estilos = $query->paginate($perPage);

            // Agregar estadísticas a cada estilo
            $estilos->getCollection()->transform(function ($estilo) {
                $estilo->estadisticas = $estilo->getEstadisticas();
                return $estilo;
            });

            return response()->json([
                'success' => true,
                'data' => $estilos,
                'filtros_aplicados' => [
                    'temporada' => $request->temporada,
                    'año_produccion' => $request->año_produccion,
                    'tipo_producto' => $request->tipo_producto,
                    'estado' => $request->estado,
                    'buscar' => $request->buscar
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los estilos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nuevo estilo
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'codigo' => 'required|string|max:50|unique:estilos,codigo',
                'nombre' => 'required|string|max:200',
                'descripcion' => 'nullable|string',
                'temporada' => 'required|string|max:50',
                'año_produccion' => 'required|integer|min:2020|max:2030',
                'costo_objetivo' => 'nullable|numeric|min:0',
                'tiempo_objetivo_min' => 'nullable|numeric|min:0',
                'tipo_producto' => 'required|in:polo,camisa,pantalon,vestido,otro',
                'estado' => 'nullable|in:desarrollo,activo,descontinuado'
            ]);

            $validated['estado'] = $validated['estado'] ?? 'desarrollo';

            $estilo = Estilo::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Estilo creado exitosamente',
                'data' => $estilo->load(['variantes', 'flujos'])
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el estilo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar estilo específico
     */
    public function show(string $id): JsonResponse
    {
        try {
            $estilo = Estilo::with([
                'variantes.color',
                'variantes.talla',
                'flujos.nodos.proceso.tipoProceso',
                'flujos.conexiones',
                'bomItems.material.categoria',
                'bomItems.material.unidadMedida',
                'bomItems.proceso'
            ])->findOrFail($id);

            $estadisticas = $estilo->getEstadisticas();

            return response()->json([
                'success' => true,
                'data' => [
                    'estilo' => $estilo,
                    'estadisticas' => $estadisticas,
                    'flujo_actual' => $estilo->flujoActual ? 
                        $estilo->flujoActual->getDatosParaReactFlow() : null
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Estilo no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el estilo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar estilo
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $estilo = Estilo::findOrFail($id);

            $validated = $request->validate([
                'codigo' => 'sometimes|string|max:50|unique:estilos,codigo,' . $id,
                'nombre' => 'sometimes|string|max:200',
                'descripcion' => 'nullable|string',
                'temporada' => 'sometimes|string|max:50',
                'año_produccion' => 'sometimes|integer|min:2020|max:2030',
                'costo_objetivo' => 'nullable|numeric|min:0',
                'tiempo_objetivo_min' => 'nullable|numeric|min:0',
                'tipo_producto' => 'sometimes|in:polo,camisa,pantalon,vestido,otro',
                'estado' => 'sometimes|in:desarrollo,activo,descontinuado'
            ]);

            $estilo->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Estilo actualizado exitosamente',
                'data' => $estilo->load(['variantes', 'flujos'])
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Estilo no encontrado'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el estilo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar estilo (soft delete)
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $estilo = Estilo::findOrFail($id);

            // Verificar si tiene variantes activas
            $variantesActivas = $estilo->variantes()->where('estado', 'activo')->count();
            if ($variantesActivas > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el estilo porque tiene variantes activas'
                ], 400);
            }

            // Marcar como descontinuado en lugar de eliminar
            $estilo->update(['estado' => 'descontinuado']);

            return response()->json([
                'success' => true,
                'message' => 'Estilo marcado como descontinuado exitosamente'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Estilo no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el estilo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener tipos de producto disponibles
     */
    public function getTiposProducto(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => Estilo::getTiposProducto()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener temporadas disponibles
     */
    public function getTemporadas(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => Estilo::getTemporadas()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener temporadas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estados disponibles
     */
    public function getEstados(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => Estilo::getEstados()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estados',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener resumen/dashboard de estilos
     */
    public function getResumen(): JsonResponse
    {
        try {
            $total = Estilo::count();
            $activos = Estilo::activos()->count();
            $enDesarrollo = Estilo::enDesarrollo()->count();
            $descontinuados = Estilo::descontinuados()->count();

            $porTipo = Estilo::selectRaw('tipo_producto, COUNT(*) as total')
                           ->groupBy('tipo_producto')
                           ->pluck('total', 'tipo_producto');

            $porTemporada = Estilo::selectRaw('temporada, COUNT(*) as total')
                                ->groupBy('temporada')
                                ->pluck('total', 'temporada');

            $estadisticasRecientes = Estilo::where('fecha_creacion', '>=', now()->subDays(30))
                                         ->selectRaw('DATE(fecha_creacion) as fecha, COUNT(*) as total')
                                         ->groupBy('fecha')
                                         ->orderBy('fecha')
                                         ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'totales' => [
                        'total' => $total,
                        'activos' => $activos,
                        'en_desarrollo' => $enDesarrollo,
                        'descontinuados' => $descontinuados
                    ],
                    'distribucion' => [
                        'por_tipo' => $porTipo,
                        'por_temporada' => $porTemporada
                    ],
                    'tendencias' => [
                        'ultimos_30_dias' => $estadisticasRecientes
                    ],
                    'porcentajes' => [
                        'activos' => $total > 0 ? round(($activos / $total) * 100, 2) : 0,
                        'desarrollo' => $total > 0 ? round(($enDesarrollo / $total) * 100, 2) : 0,
                        'descontinuados' => $total > 0 ? round(($descontinuados / $total) * 100, 2) : 0
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener resumen de estilos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Duplicar estilo
     */
    public function duplicar(string $id): JsonResponse
    {
        try {
            $estiloOriginal = Estilo::with(['bomItems', 'flujos.nodos', 'flujos.conexiones'])
                                   ->findOrFail($id);

            $nuevoEstilo = Estilo::create([
                'codigo' => $estiloOriginal->codigo . '_COPIA',
                'nombre' => $estiloOriginal->nombre . ' (Copia)',
                'descripcion' => $estiloOriginal->descripcion,
                'temporada' => $estiloOriginal->temporada,
                'año_produccion' => $estiloOriginal->año_produccion,
                'costo_objetivo' => $estiloOriginal->costo_objetivo,
                'tiempo_objetivo_min' => $estiloOriginal->tiempo_objetivo_min,
                'tipo_producto' => $estiloOriginal->tipo_producto,
                'estado' => 'desarrollo'
            ]);

            // Copiar BOM
            foreach ($estiloOriginal->bomItems as $bomItem) {
                $nuevoEstilo->bomItems()->create([
                    'id_material' => $bomItem->id_material,
                    'cantidad_base' => $bomItem->cantidad_base,
                    'id_proceso' => $bomItem->id_proceso,
                    'aplica_talla' => $bomItem->aplica_talla,
                    'aplica_color' => $bomItem->aplica_color,
                    'es_critico' => $bomItem->es_critico
                ]);
            }

            // Copiar flujo actual si existe
            $flujoActual = $estiloOriginal->flujoActual;
            if ($flujoActual) {
                $flujoActual->duplicarConNuevaVersion();
            }

            return response()->json([
                'success' => true,
                'message' => 'Estilo duplicado exitosamente',
                'data' => $nuevoEstilo->load(['bomItems', 'flujos'])
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Estilo no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al duplicar el estilo',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
