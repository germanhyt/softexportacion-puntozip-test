<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\CategoriaMaterial;
use App\Models\UnidadMedida;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class MaterialController extends Controller
{
    /**
     * Listar materiales con filtros y paginación
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Material::with(['categoria', 'unidadMedida', 'colores']);

            // Aplicar filtros
            if ($request->has('categoria_id') && $request->categoria_id) {
                $query->porCategoria($request->categoria_id);
            }

            if ($request->has('tipo_material') && $request->tipo_material !== 'todos') {
                $query->porTipo($request->tipo_material);
            }

            if ($request->has('estado') && $request->estado !== 'todos') {
                if ($request->estado === 'activos') {
                    $query->activos();
                } else {
                    $query->inactivos();
                }
            }

            if ($request->has('es_critico') && $request->es_critico !== 'todos') {
                if ($request->es_critico === 'si') {
                    $query->criticos();
                }
            }

            if ($request->has('stock_estado') && $request->stock_estado !== 'todos') {
                switch ($request->stock_estado) {
                    case 'con_stock':
                        $query->conStock();
                        break;
                    case 'sin_stock':
                        $query->sinStock();
                        break;
                }
            }

            if ($request->has('proveedor') && $request->proveedor) {
                $query->porProveedor($request->proveedor);
            }

            if ($request->has('buscar') && $request->buscar) {
                $query->buscar($request->buscar);
            }

            // Ordenamiento
            $ordenPor = $request->get('orden_por', 'nombre');
            $direccion = $request->get('direccion', 'asc');
            $query->orderBy($ordenPor, $direccion);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $materiales = $query->paginate($perPage);

            // Agregar información adicional
            $materiales->getCollection()->transform(function ($material) {
                $material->info_consumo = $material->getInfoConsumo();
                $material->descripcion_completa = $material->descripcion_completa;
                $material->costo_formateado = $material->costo_formateado;
                $material->estado_stock = $material->estado_stock;
                return $material;
            });

            return response()->json([
                'success' => true,
                'data' => $materiales,
                'filtros_aplicados' => [
                    'categoria_id' => $request->categoria_id,
                    'tipo_material' => $request->tipo_material,
                    'estado' => $request->estado,
                    'es_critico' => $request->es_critico,
                    'stock_estado' => $request->stock_estado,
                    'proveedor' => $request->proveedor,
                    'buscar' => $request->buscar
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los materiales',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nuevo material
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'codigo' => 'required|string|max:50|unique:materiales,codigo',
                'nombre' => 'required|string|max:200',
                'id_categoria' => 'required|exists:categorias_materiales,id',
                'id_unidad_medida' => 'required|exists:unidades_medida,id',
                'costo_unitario' => 'required|numeric|min:0',
                'stock_actual' => 'nullable|numeric|min:0',
                'proveedor' => 'nullable|string|max:200',
                'tipo_material' => 'required|in:hilo,tinte,quimico,tinta,avio,empaque',
                'es_critico' => 'boolean',
                'estado' => 'nullable|in:activo,inactivo'
            ]);

            $validated['estado'] = $validated['estado'] ?? 'activo';
            $validated['stock_actual'] = $validated['stock_actual'] ?? 0;
            $validated['es_critico'] = $validated['es_critico'] ?? false;

            $material = Material::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Material creado exitosamente',
                'data' => $material->load(['categoria', 'unidadMedida'])
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
                'message' => 'Error al crear el material',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar material específico
     */
    public function show(string $id): JsonResponse
    {
        try {
            $material = Material::with([
                'categoria',
                'unidadMedida',
                'colores',
                'bomItems.estilo',
                'procesoInputs.proceso'
            ])->findOrFail($id);

            $infoConsumo = $material->getInfoConsumo();

            return response()->json([
                'success' => true,
                'data' => [
                    'material' => $material,
                    'info_consumo' => $infoConsumo,
                    'estadisticas' => [
                        'descripcion_completa' => $material->descripcion_completa,
                        'costo_formateado' => $material->costo_formateado,
                        'estado_stock' => $material->estado_stock
                    ]
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Material no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el material',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar material
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $material = Material::findOrFail($id);

            $validated = $request->validate([
                'codigo' => 'sometimes|string|max:50|unique:materiales,codigo,' . $id,
                'nombre' => 'sometimes|string|max:200',
                'id_categoria' => 'sometimes|exists:categorias_materiales,id',
                'id_unidad_medida' => 'sometimes|exists:unidades_medida,id',
                'costo_unitario' => 'sometimes|numeric|min:0',
                'stock_actual' => 'sometimes|numeric|min:0',
                'proveedor' => 'nullable|string|max:200',
                'tipo_material' => 'sometimes|in:hilo,tinte,quimico,tinta,avio,empaque',
                'es_critico' => 'sometimes|boolean',
                'estado' => 'sometimes|in:activo,inactivo'
            ]);

            $material->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Material actualizado exitosamente',
                'data' => $material->load(['categoria', 'unidadMedida'])
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Material no encontrado'
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
                'message' => 'Error al actualizar el material',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar material (soft delete)
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $material = Material::findOrFail($id);

            // Verificar si está siendo usado en algún BOM
            $enUso = $material->bomItems()->exists();
            if ($enUso) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el material porque está siendo usado en BOMs de estilos'
                ], 400);
            }

            // Marcar como inactivo en lugar de eliminar
            $material->update(['estado' => 'inactivo']);

            return response()->json([
                'success' => true,
                'message' => 'Material marcado como inactivo exitosamente'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Material no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el material',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener tipos de material disponibles
     */
    public function getTiposMaterial(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => Material::getTiposMaterial()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de material',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener categorías disponibles
     */
    public function getCategorias(): JsonResponse
    {
        try {
            $categorias = CategoriaMaterial::activas()->orderBy('nombre')->get();
            
            return response()->json([
                'success' => true,
                'data' => $categorias
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener categorías',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener unidades de medida disponibles
     */
    public function getUnidadesMedida(): JsonResponse
    {
        try {
            $unidades = UnidadMedida::activas()->orderBy('nombre')->get();
            
            return response()->json([
                'success' => true,
                'data' => $unidades
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener unidades de medida',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener materiales críticos con stock bajo
     */
    public function getMaterialesCriticos(): JsonResponse
    {
        try {
            $umbral = request()->get('umbral', 10);
            $materiales = Material::criticosConStockBajo($umbral);

            return response()->json([
                'success' => true,
                'data' => $materiales,
                'umbral_usado' => $umbral,
                'total_criticos' => $materiales->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener materiales críticos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener materiales por tipo específico
     */
    public function getPorTipo(string $tipo): JsonResponse
    {
        try {
            $materiales = Material::porTipoMaterial($tipo);

            return response()->json([
                'success' => true,
                'data' => $materiales,
                'tipo' => $tipo,
                'total' => $materiales->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener materiales por tipo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar stock de material
     */
    public function actualizarStock(Request $request, string $id): JsonResponse
    {
        try {
            $material = Material::findOrFail($id);

            $validated = $request->validate([
                'nuevo_stock' => 'required|numeric|min:0',
                'motivo' => 'nullable|string|max:500'
            ]);

            $stockAnterior = $material->stock_actual;
            $material->update(['stock_actual' => $validated['nuevo_stock']]);

            // Aquí podrías registrar el movimiento de stock en una tabla de auditoría
            // MovimientoStock::create([...]);

            return response()->json([
                'success' => true,
                'message' => 'Stock actualizado exitosamente',
                'data' => [
                    'material' => $material,
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => $validated['nuevo_stock'],
                    'diferencia' => $validated['nuevo_stock'] - $stockAnterior,
                    'motivo' => $validated['motivo'] ?? 'Ajuste manual'
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Material no encontrado'
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
                'message' => 'Error al actualizar el stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener resumen de materiales
     */
    public function getResumen(): JsonResponse
    {
        try {
            $total = Material::count();
            $activos = Material::activos()->count();
            $criticos = Material::criticos()->count();
            $sinStock = Material::sinStock()->count();
            $stockBajo = Material::where('stock_actual', '<=', 10)->count();

            $porTipo = Material::selectRaw('tipo_material, COUNT(*) as total')
                              ->groupBy('tipo_material')
                              ->pluck('total', 'tipo_material');

            $valorInventario = Material::selectRaw('SUM(stock_actual * costo_unitario) as valor_total')
                                     ->first()->valor_total ?? 0;

            $topProveedores = Material::selectRaw('proveedor, COUNT(*) as total_materiales')
                                    ->whereNotNull('proveedor')
                                    ->groupBy('proveedor')
                                    ->orderByDesc('total_materiales')
                                    ->limit(5)
                                    ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'totales' => [
                        'total' => $total,
                        'activos' => $activos,
                        'criticos' => $criticos,
                        'sin_stock' => $sinStock,
                        'stock_bajo' => $stockBajo
                    ],
                    'distribucion' => [
                        'por_tipo' => $porTipo
                    ],
                    'inventario' => [
                        'valor_total' => $valorInventario,
                        'promedio_por_material' => $total > 0 ? $valorInventario / $total : 0
                    ],
                    'proveedores' => [
                        'top_5' => $topProveedores
                    ],
                    'alertas' => [
                        'materiales_criticos' => $criticos,
                        'sin_stock' => $sinStock,
                        'stock_bajo' => $stockBajo
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener resumen de materiales',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
