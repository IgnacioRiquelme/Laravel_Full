<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RequerimientoController;
use App\Http\Controllers\MallaController;

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

    // === MALLA DE PROCESOS ===
    Route::prefix('procesos')->group(function () {
        Route::get('/malla', [MallaController::class, 'index'])->name('procesos.malla');
        Route::post('/actualizar/{id}', [MallaController::class, 'actualizar'])->name('procesos.actualizar'); // ✅ Corregido
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
