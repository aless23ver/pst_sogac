<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminRequisitoController;
use App\Http\Controllers\AdminDashboardController;

// Grupo protegido para el Admin
Route::prefix('admin')->group(function () {
    
    // Rutas del Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/dashboard/estado/{id}/{accion}', [AdminDashboardController::class, 'cambiarEstado'])->name('admin.dashboard.estado');

    // Rutas de Requisitos (Laravel mapea todo el CRUD automáticamente)
    Route::resource('requisitos', AdminRequisitoController::class)->names('admin.requisitos');
});


use App\Http\Controllers\PreguntasFrecuentesController;

Route::resource('soporte', PreguntasFrecuentesController::class);


// TEMPORAL VVV

Route::get('/prueba-chat-usuario', function () {
    // Simulamos un hilo en estado 'pendiente_cierre' para ver todos los botones
    $hilo = (object) [
        'hch_id' => 1,
        'hch_estado' => 'pendiente_cierre',
        'hch_etiqueta_tema' => null
    ];
    return view('soporte\chat_usuario', compact('hilo'));
});

Route::get('/prueba-chat-admin', function () {
    // Simulamos un hilo en estado 'activo' para ver el formulario de cierre
    $hilo = (object) [
        'hch_id' => 1,
        'hch_estado' => 'activo',
    ];
    return view('soporte\chat_admin', compact('hilo'));
});

// TEMPORAL ^^^

/*
Route::get('/', function () {
    return view('welcome');
});
*/

//User routes
Route::prefix('user')->group(function () {

    Route::get('/', function () {
        return view('user.usrDashboard');
    });

    Route::get('/dateTemplate', function () {
        return view('user.dateTemplate'); });
});



//Admin routes
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    
    Route::get('/admin', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    
    // Ruta para aprobar/rechazar
    Route::get('/solicitudes/{solicitud}/{accion}', [AdminDashboardController::class, 'cambiarEstado'])->name('admin.solicitudes.estado');

    // Esta sola línea crea TODAS las rutas para Crear, Leer, Actualizar y Eliminar
    Route::resource('requisitos', AdminRequisitoController::class)->names('admin.requisitos');
});
