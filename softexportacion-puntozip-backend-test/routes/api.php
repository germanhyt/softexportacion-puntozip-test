<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EstiloController;
use App\Http\Controllers\Api\MaterialController;
use App\Http\Controllers\Api\ProcesoController;
use App\Http\Controllers\Api\TallaController;
use App\Http\Controllers\Api\ColorController;
use App\Http\Controllers\Api\BomEstiloController;
use App\Http\Controllers\Api\FlujoController;
use App\Http\Controllers\Api\CalculoVarianteController;

// Grupo de rutas de la API textil v1
Route::prefix('v1')->group(function () {
    
    // ===== ESTILOS =====
    Route::prefix('estilos')->group(function () {
        Route::get('/', [EstiloController::class, 'index']);
        Route::post('/', [EstiloController::class, 'store']);
        Route::get('/resumen', [EstiloController::class, 'getResumen']);
        Route::get('/tipos-producto', [EstiloController::class, 'getTiposProducto']);
        Route::get('/temporadas', [EstiloController::class, 'getTemporadas']);
        Route::get('/estados', [EstiloController::class, 'getEstados']);
        
        Route::get('/{id}', [EstiloController::class, 'show']);
        Route::put('/{id}', [EstiloController::class, 'update']);
        Route::delete('/{id}', [EstiloController::class, 'destroy']);
        Route::post('/{id}/duplicar', [EstiloController::class, 'duplicar']);
        
        // BOM del estilo
        Route::get('/{id}/bom', [BomEstiloController::class, 'porEstilo']);
        Route::post('/{id}/bom', [BomEstiloController::class, 'actualizarBom']);
        Route::post('/{id}/bom/calcular-variante', [BomEstiloController::class, 'calcularPorVariante']);
        
        // Flujos del estilo
        Route::get('/{estilo_id}/flujos', [FlujoController::class, 'listarFlujosPorEstilo']);
        Route::get('/{estilo_id}/flujos/{flujo_id}', [FlujoController::class, 'obtenerFlujo']);
        Route::post('/{estilo_id}/flujos', [FlujoController::class, 'guardarFlujo']);
        
        // Cálculos de variantes
        Route::post('/{id}/calcular-variante-textil', [CalculoVarianteController::class, 'calcularVariante']);
        Route::get('/{estilo_id}/variantes/{color_id}/{talla_id}/historial', [CalculoVarianteController::class, 'obtenerHistorial']);
    });

    // ===== MATERIALES =====
    Route::get('materiales/tipos-material', [MaterialController::class, 'getTiposMaterial']);
    Route::get('materiales/criticos', [MaterialController::class, 'getMaterialesCriticos']);
    Route::apiResource('materiales', MaterialController::class);

    // ===== PROCESOS =====
    Route::get('procesos/tipos-proceso', [ProcesoController::class, 'getTiposProceso']);
    Route::get('procesos/para-reactflow', [ProcesoController::class, 'getParaReactFlow']);
    Route::get('procesos/{id}/sop', [ProcesoController::class, 'getSOP']);
    Route::apiResource('procesos', ProcesoController::class);

    // ===== COLORES =====
    Route::apiResource('colores', ColorController::class);

    // ===== TALLAS =====
    Route::get('tallas/disponibles', [TallaController::class, 'getTallasDisponibles']);
    Route::apiResource('tallas', TallaController::class);

    // ===== FLUJOS =====
    Route::patch('flujos/{flujo_id}/posiciones', [FlujoController::class, 'actualizarPosiciones']);
    Route::delete('flujos/{flujo_id}', [FlujoController::class, 'eliminarFlujo']);
    Route::get('flujos/{flujo_id}/calcular-tiempo', [FlujoController::class, 'calcularTiempoTotal']);

    // ===== UTILIDADES =====
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
            'data' => ['Tejido', 'Teñido', 'Corte', 'Confección', 'Estampado', 'Acabado', 'Lavado', 'Control de Calidad', 'Empaque']
        ]);
    });
});
