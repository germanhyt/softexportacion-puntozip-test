<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CategoriaMaterialController;
use App\Http\Controllers\Api\MaterialController;
use App\Http\Controllers\Api\EstiloController;
use App\Http\Controllers\Api\FlujoController;
use App\Http\Controllers\Api\ProcesoController;
use App\Http\Controllers\Api\ColorController;
use App\Http\Controllers\Api\BomEstiloController;

/*
|--------------------------------------------------------------------------
| API Routes - Sistema Textil
|--------------------------------------------------------------------------
|
| Rutas API para el sistema de gestión textil que incluye:
| - Gestión de materiales y categorías
| - Gestión de estilos y variantes
| - Gestión de flujos de procesos para @xyflow/react
| - Cálculos de costos y tiempos
|
*/

Route::prefix('v1')->group(function () {
    
    // ===== CATEGORÍAS DE MATERIALES =====
    Route::apiResource('categorias-materiales', CategoriaMaterialController::class);

    // ===== MATERIALES =====
    Route::apiResource('materiales', MaterialController::class);
    Route::post('materiales/{id}/colores', [MaterialController::class, 'asociarColores']);

    // ===== ESTILOS =====
    Route::apiResource('estilos', EstiloController::class);
    Route::get('estilos/{id}/costos', [EstiloController::class, 'calcularCostos']);
    Route::post('estilos/{id}/calcular-variante', [EstiloController::class, 'calcularPorVariante']);

    // ===== PROCESOS =====
    Route::apiResource('procesos', ProcesoController::class);
    Route::get('procesos/{id}/sop', [ProcesoController::class, 'obtenerSOP']);

    // ===== COLORES =====
    Route::apiResource('colores', ColorController::class);

    // ===== BOM (BILL OF MATERIALS) =====
    Route::get('estilos/{id}/bom', [BomEstiloController::class, 'porEstilo']);
    Route::post('estilos/{id}/bom', [BomEstiloController::class, 'actualizarBom']);
    Route::post('estilos/{id}/bom/calcular-variante', [BomEstiloController::class, 'calcularPorVariante']);

    // ===== FLUJOS DE PROCESOS =====
    // Listar flujos por estilo
    Route::get('estilos/{estilo_id}/flujos', [FlujoController::class, 'listarFlujosPorEstilo']);
    
    // Obtener flujo completo para el editor visual
    Route::get('estilos/{estilo_id}/flujos/{flujo_id}', [FlujoController::class, 'obtenerFlujo']);
    
    // Guardar nuevo flujo desde el editor visual
    Route::post('estilos/{estilo_id}/flujos', [FlujoController::class, 'guardarFlujo']);
    
    // Actualizar posiciones de nodos en tiempo real
    Route::patch('flujos/{flujo_id}/posiciones', [FlujoController::class, 'actualizarPosiciones']);
    
    // Eliminar flujo completo
    Route::delete('flujos/{flujo_id}', [FlujoController::class, 'eliminarFlujo']);
    
    // Calcular tiempo y costo total del flujo
    Route::get('flujos/{flujo_id}/calcular-tiempo', [FlujoController::class, 'calcularTiempoTotal']);

    // ===== RUTAS DE UTILIDADES =====
    
    // Obtener datos para dropdowns/selects
    Route::get('utils/temporadas', function () {
        return response()->json([
            'success' => true,
            'data' => ['primavera', 'verano', 'otoño', 'invierno']
        ]);
    });

    Route::get('utils/estados', function () {
        return response()->json([
            'success' => true,
            'data' => ['activo', 'inactivo', 'desarrollo']
        ]);
    });

    Route::get('utils/años-produccion', function () {
        return response()->json([
            'success' => true,
            'data' => range(2020, 2030)
        ]);
    });

    Route::get('utils/tipos-proceso', function () {
        return response()->json([
            'success' => true,
            'data' => ['corte', 'costura', 'terminado', 'control_calidad', 'empaque']
        ]);
    });

    // ===== RUTAS PARA REPORTES Y DASHBOARD =====
    
    Route::get('dashboard/resumen', function () {
        // Esta sería una ruta para obtener datos del dashboard
        return response()->json([
            'success' => true,
            'data' => [
                'total_estilos' => 0, // Se implementaría la lógica real
                'total_materiales' => 0,
                'estilos_activos' => 0,
                'flujos_creados' => 0
            ]
        ]);
    });

    Route::get('reportes/costos-por-estilo', function () {
        // Reporte de costos por estilo
        return response()->json([
            'success' => true,
            'data' => [] // Se implementaría la lógica real
        ]);
    });

    // ===== RUTAS PARA BÚSQUEDA GLOBAL =====
    
    Route::get('buscar', function (\Illuminate\Http\Request $request) {
        $termino = $request->get('q', '');
        
        if (empty($termino)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'estilos' => [],
                    'materiales' => []
                ]
            ]);
        }

        // Implementar búsqueda en múltiples modelos
        return response()->json([
            'success' => true,
            'data' => [
                'estilos' => [], // App\Models\Estilo::where('nombre', 'like', "%{$termino}%")->limit(5)->get()
                'materiales' => [] // App\Models\Material::where('nombre', 'like', "%{$termino}%")->limit(5)->get()
            ]
        ]);
    });
});

// ===== RUTAS PARA HEALTH CHECK =====
Route::get('health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0'
    ]);
});

// ===== RUTAS PARA CORS PREFLIGHT =====
Route::options('{any}', function () {
    return response()->json([], 200);
})->where('any', '.*');
