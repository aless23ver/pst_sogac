<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminRequisitoController;
use App\Http\Controllers\PreguntasFrecuentesController;
use App\Http\Controllers\UserSolicitudController;


Route::get('/', function () {
    return view('welcome');
});

//User routes
Route::prefix('user')->group(function () {

    Route::get('/', function () {
        return view('user.dashboard');
    });
        });

// Grupo protegido para el Admin
Route::prefix('admin')->group(function () {
    
    // Rutas del Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/dashboard/estado/{id}/{accion}', [AdminDashboardController::class, 'cambiarEstado'])->name('admin.dashboard.estado');

    // Rutas de Requisitos (Laravel mapea todo el CRUD automáticamente)
    Route::resource('requisitos', AdminRequisitoController::class)->names('admin.requisitos');
});

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
