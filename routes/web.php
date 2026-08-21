<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminRequisitoController;

use App\Http\Controllers\PreguntasFrecuentesController;

// Asumimos que tienes un middleware 'auth' (usuario logueado) 
// y uno 'admin' (que verifica el rol)

Route::middleware(['auth', 'admin'])->group(function () {
    
    Route::get('/admin', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    
    // Ruta para aprobar/rechazar
    Route::get('/admin/solicitudes/{solicitud}/{accion}', [AdminDashboardController::class, 'cambiarEstado'])->name('admin.solicitudes.estado');

    // Esta sola línea crea TODAS las rutas para Crear, Leer, Actualizar y Eliminar
    Route::resource('admin/requisitos', AdminRequisitoController::class)->names('admin.requisitos');
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

