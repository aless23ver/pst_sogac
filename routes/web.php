<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminRequisitoController;

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