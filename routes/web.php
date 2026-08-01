<?php

<<<<<<< HEAD
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminRequisitoController;

// Asumimos que tienes un middleware 'auth' (usuario logueado) 
// y uno 'admin' (que verifica el rol)

Route::middleware(['auth', 'admin'])->group(function () {
    
    Route::get('/admin', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    
    // Ruta para aprobar/rechazar
    Route::get('/admin/solicitudes/{solicitud}/{accion}', [AdminDashboardController::class, 'cambiarEstado'])->name('admin.solicitudes.estado');

    // Esta sola línea crea TODAS las rutas para Crear, Leer, Actualizar y Eliminar
    Route::resource('admin/requisitos', AdminRequisitoController::class)->names('admin.requisitos');
});
=======
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
>>>>>>> 95ff0d6c6e3574ab242a8164fae37a696f1a7f7c
