<?php

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

// TEMPORAL? VVV

use App\Http\Controllers\SoporteController;

// Protegemos todas las rutas de chat para que requieran sesión iniciada
Route::middleware(['auth'])->group(function () {
    
    // Rutas compartidas (Las usan tanto usuarios como admins)
    Route::get('/soporte/chat/{id}', [SoporteController::class, 'mostrarChat'])->name('chat.mostrar');
    Route::post('/soporte/chat/{id}/mensaje', [SoporteController::class, 'enviarMensaje'])->name('chat.enviar');
    Route::get('/soporte/historial', [SoporteController::class, 'verHistorial'])->name('chat.historial');

    // Rutas exclusivas del Usuario (Estudiante)
    Route::post('/soporte/iniciar', [SoporteController::class, 'iniciarChat'])->name('chat.iniciar');
    Route::post('/soporte/chat/{id}/confirmar', [SoporteController::class, 'confirmarCierre'])->name('chat.confirmar');
    Route::post('/soporte/chat/{id}/rechazar', [SoporteController::class, 'desconfirmarCierre'])->name('chat.rechazar');

    // Rutas exclusivas del Admin
    Route::post('/soporte/chat/{id}/reclamar', [SoporteController::class, 'reclamarChat'])->name('chat.reclamar');
    Route::post('/soporte/chat/{id}/proponer-cierre', [SoporteController::class, 'proponerCierre'])->name('chat.proponer_cierre');
    
});
// TEMPORAL? ^^^
use App\Http\Controllers\PreguntasFrecuentesController;

Route::resource('soporte', PreguntasFrecuentesController::class);
// LOGIN
use App\Http\Controllers\LoginController;

Route::get('/login', [LoginController::class, 'mostrarFormulario'])->name('login');
Route::post('/login', [LoginController::class, 'procesarLogin'])->name('login.post');
Route::post('/logout', [LoginController::class, 'cerrarSesion'])->name('logout');

use App\Http\Controllers\RegisterController;

// Rutas de Registro
Route::get('/register', [RegisterController::class, 'mostrarFormulario'])->name('register');
Route::post('/register', [RegisterController::class, 'registrar'])->name('register.post');
// LOGIN


//LOGOUT RAPIDO DE DEPURACION
use Illuminate\Support\Facades\Auth;

Route::get('/salir-rapido', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
});
//LOGOUT RAPIDO DE DEPURACION