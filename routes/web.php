<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RequerimientoController;
use App\Http\Controllers\MallaController;
use App\Http\Middleware\EnsureDatabaseConnection;
use App\Http\Controllers\CargaRequerimientosController;
use App\Http\Controllers\IncidenteController;
use App\Http\Controllers\EstadoIncidenteController;
use App\Http\Controllers\NegocioIncidenteController;
use App\Http\Controllers\AmbienteIncidenteController;
use App\Http\Controllers\CapaIncidenteController;
use App\Http\Controllers\ServidorIncidenteController;
use App\Http\Controllers\EventoIncidenteController;
use App\Http\Controllers\AccionIncidenteController;
use App\Http\Controllers\EscaladoIncidenteController;
use App\Http\Controllers\TipoRequerimientoIncidenteController;


// Ruta para test de conexión (ping DB)
Route::get('/ping-db', function () {
    try {
        \Illuminate\Support\Facades\DB::select('SELECT 1');
        return response()->json(['status' => 'ok']);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
});

Route::get('/', fn () => view('welcome'));

// Rutas protegidas por autenticación
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');

    // Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Menús por rol
    Route::get('/menu-analista', fn () => view('menus.analista-menu'))->name('menu.analista');
    Route::get('/menu-operador', fn () => view('menus.operador-menu'))->name('menu.operador');

    // === REQUERIMIENTOS ===
    Route::get('/requerimientos/create', [RequerimientoController::class, 'create'])->name('requerimientos.create');
    Route::get('/requerimientos/{ticket}/edit', [RequerimientoController::class, 'edit'])->name('requerimientos.edit');
    Route::post('/requerimientos', [RequerimientoController::class, 'store'])->name('requerimientos.store');
    Route::put('/requerimientos/{ticket}', [RequerimientoController::class, 'update'])->name('requerimientos.update');
    Route::post('/requerimientos/filtrados', [RequerimientoController::class, 'filtrados'])->name('requerimientos.filtrados');
    Route::get('/requerimientos/filtrados', [RequerimientoController::class, 'filtrados'])->name('requerimientos.filtrados');
    Route::get('/requerimientos/dia', [RequerimientoController::class, 'vistaRequerimientosDelDia'])->name('requerimientos.dia');
    Route::post('/requerimientos/exportar', [RequerimientoController::class, 'exportarExcel'])->name('requerimientos.exportar');
    Route::post('/solicitantes', [RequerimientoController::class, 'storeSolicitante'])->name('solicitantes.store');
    Route::get('/carga/requerimientos', [CargaRequerimientosController::class, 'form'])->name('carga.requerimientos.form');
    Route::post('/carga/requerimientos', [CargaRequerimientosController::class, 'importar'])->name('carga.requerimientos.importar');

    // === INCIDENTES ===
    Route::get('/incidentes/create', [IncidenteController::class, 'create'])->name('incidentes.create');
    Route::post('/incidentes', [IncidenteController::class, 'store'])->name('incidentes.store');
    Route::get('/incidentes', [IncidenteController::class, 'index'])->name('incidentes.index');
    Route::get('/incidentes/search-proceso', [App\Http\Controllers\IncidenteController::class, 'searchProceso'])->name('incidentes.search-proceso');  
    
    // === ACTUALIZACIONES INCIDENTES COMBOBOX ===

    Route::post('/estados_incidentes', [EstadoIncidenteController::class, 'store'])->name('estados_incidentes.store');
Route::post('/negocio_incidentes', [NegocioIncidenteController::class, 'store'])->name('negocio_incidentes.store');
Route::post('/ambiente_incidentes', [AmbienteIncidenteController::class, 'store'])->name('ambiente_incidentes.store');
Route::post('/capa_incidentes', [CapaIncidenteController::class, 'store'])->name('capa_incidentes.store');
Route::post('/servidor_incidentes', [ServidorIncidenteController::class, 'store'])->name('servidor_incidentes.store');
Route::post('/evento_incidentes', [EventoIncidenteController::class, 'store'])->name('evento_incidentes.store');
Route::post('/accion_incidentes', [AccionIncidenteController::class, 'store'])->name('accion_incidentes.store');
Route::post('/escalado_incidentes', [EscaladoIncidenteController::class, 'store'])->name('escalado_incidentes.store');
Route::post('/tipo_requerimiento_incidentes', [TipoRequerimientoIncidenteController::class, 'store'])->name('tipo_requerimiento_incidentes.store');

    // === MALLA DE PROCESOS ===
    Route::prefix('procesos')->group(function () {
        Route::get('/malla', [MallaController::class, 'index'])->name('procesos.malla');
        Route::post('/actualizar/{id}', [MallaController::class, 'actualizar'])->name('procesos.actualizar');
        Route::post('/cerrar-dia', [MallaController::class, 'cerrarDia'])->name('procesos.cerrar-dia');
        Route::get('/exportar/{fecha}', [MallaController::class, 'exportar'])->name('procesos.exportar');

        // ✅ NUEVA RUTA: vista histórica
        Route::get('/historico/{fecha}', [MallaController::class, 'historico'])->name('procesos.historico');
    });

    // Rutas DB conexión protegidas
    Route::middleware([EnsureDatabaseConnection::class])->group(function () {
        Route::resource('procesos', MallaController::class);
    });

    // API para edición de requerimientos
    Route::get('/api/requerimientos/{ticket}', function ($ticket) {
        $req = \App\Models\Requerimiento::where('numero_ticket', $ticket)->first();
        return $req
            ? ['existe' => true, 'requerimiento' => collect($req)->only([
                'solicitante', 'requerimiento', 'negocio', 'ambiente',
                'capa', 'servidor', 'estado', 'tipo_solicitud',
                'tipo_pase', 'ic', 'observaciones'
            ])]
            : ['existe' => false];
    })->name('requerimientos.api.buscar');
});

require __DIR__.'/auth.php';
