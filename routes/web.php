<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminDatosEstudiantesController;
use App\Http\Controllers\AdminDatosPrecargaController;
use App\Http\Controllers\AdminEstadisticasController;
use App\Http\Controllers\AdminHistorialCambioController;
use App\Http\Controllers\AdminPreguntaFrecuenteController;
use App\Http\Controllers\AdminRequisitoController;
use App\Http\Controllers\AdminSolicitudController;
use App\Http\Controllers\AdminTipoSolicitudController;
use App\Http\Controllers\AdminTratadasController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AyudaController;
use App\Http\Controllers\HistorialSolicitudController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\SoporteController;
use App\Http\Controllers\UserDatosPerfilController;
use App\Http\Controllers\UserSolicitudController;
use Illuminate\Support\Facades\Route;

// El homepage institucional es la pagina principal y se muestra siempre,
// haya sesion o no: quien ya esta dentro del sistema tiene su acceso directo
// en el boton "Mi panel" de la barra del homepage. El sistema en si vive en
// /login, /user y /admin.
Route::view('/', 'portada')->name('portada');

Route::get('/login', [LoginController::class, 'mostrarFormulario'])->name('login');
Route::post('/login', [LoginController::class, 'procesarLogin'])->name('login.post');

Route::get('/register', [RegisterController::class, 'mostrarFormulario'])->name('register');
Route::post('/register', [RegisterController::class, 'registrar'])->name('register.post');

// Recuperacion de contrasena: pide el correo, envia el enlace y permite
// escribir la nueva clave. Usa el broker de Laravel sobre la tabla
// usuarios a traves del campo usu_correo_electronico.
Route::get('/olvide-mi-contrasena', [PasswordResetController::class, 'requestForm'])->name('password.request');
Route::post('/olvide-mi-contrasena', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
Route::get('/restablecer-contrasena/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
Route::post('/restablecer-contrasena', [PasswordResetController::class, 'updatePassword'])->name('password.update');

Route::post('/logout', [LoginController::class, 'cerrarSesion'])->name('logout')->middleware('auth');

Route::middleware('auth')->prefix('user')->group(function () {
    Route::get('/dashboard', [UserSolicitudController::class, 'index'])->name('dashboard');
    Route::get('/citas', [UserSolicitudController::class, 'misCitas'])->name('user.citas');

    Route::get('/datos-perfil', [UserDatosPerfilController::class, 'index'])->name('user.datos-perfil');
    Route::post('/datos-perfil', [UserDatosPerfilController::class, 'update'])->name('user.datos-perfil.update');

    Route::get('/tramites', [UserSolicitudController::class, 'listarTramites'])->name('user.tramites.index');

    // Historial de solicitudes del estudiante. Antes era una sola pagina plana
    // (user.solicitudes.historial, en /user/historial) con cuatro columnas; ahora
    // es un sector con listado filtrable y ficha de detalle.
    Route::prefix('historial')->name('user.historial.')->group(function () {
        Route::get('/', [HistorialSolicitudController::class, 'index'])->name('index');
        Route::get('/{solicitud}', [HistorialSolicitudController::class, 'show'])->name('show');
    });

    Route::get('/tramites/{id}/solicitar', [UserSolicitudController::class, 'create'])->name('user.tramites.solicitar');
    Route::post('/tramites/{id}/solicitar', [UserSolicitudController::class, 'store'])->name('user.tramites.store');

    // Ayuda: las preguntas frecuentes y el chat son dos sectores distintos.
    // Antes ambos vivian bajo "soporte", lo que hacia que /user/soporte
    // mostrara las FAQ y el chat quedara escondido debajo del mismo nombre.
    Route::prefix('ayuda')->name('user.ayuda.')->group(function () {
        Route::get('/preguntas', [AyudaController::class, 'preguntas'])->name('preguntas');

        Route::prefix('chat')->name('chat.')->group(function () {
            Route::get('/', [SoporteController::class, 'verHistorial'])->name('index');
            Route::post('/iniciar', [SoporteController::class, 'iniciarChat'])->name('iniciar');
            Route::get('/{id}', [SoporteController::class, 'mostrarChat'])->name('mostrar');
            Route::post('/{id}/mensaje', [SoporteController::class, 'enviarMensaje'])->name('enviar');
            Route::post('/{id}/confirmar', [SoporteController::class, 'confirmarCierre'])->name('confirmar');
            Route::post('/{id}/rechazar', [SoporteController::class, 'desconfirmarCierre'])->name('rechazar');
        });
    });
});

// ============================================================
// GRUPO ADMIN
// 'admin' (middleware EsAdmin) exige sesión + que el usuario pertenezca
// a la jerarquía administrativa: administrador, analista o taquillero.
// Dentro, cada módulo se afina con 'rol' según la jerarquía
// (definida en App\Models\Rol):
//   administrador > analista > taquillero
// ============================================================
Route::middleware('admin')->prefix('admin')->group(function () {

    // ---- Solo el ADMINISTRADOR ----

    // Panel estadístico: métricas precisas de todo el proceso de las solicitudes.
    Route::middleware('rol:administrador')->group(function () {
        Route::get('/estadisticas', [AdminEstadisticasController::class, 'index'])->name('admin.estadisticas');
        Route::get('/estadisticas/exportar', [AdminEstadisticasController::class, 'exportar'])->name('admin.estadisticas.exportar');
    });

    // Bitácora de cambios: qué se tocó en el sistema, quién y con qué valores.
    // Es un sector aparte y no una pestaña del panel estadístico porque mide
    // cosas distintas: el panel resume los números del proceso de solicitudes y
    // la bitácora deja constancia de cada modificación, con su autor.
    Route::middleware('rol:administrador')->prefix('cambios')->name('admin.cambios.')->group(function () {
        Route::get('/', [AdminHistorialCambioController::class, 'index'])->name('index');
        Route::get('/exportar', [AdminHistorialCambioController::class, 'exportar'])->name('exportar');
    });

    // ---- Administrador y analista ----

    Route::middleware('rol:administrador,analista')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

        // Solicitudes tratadas: todo lo que ya se movió de estado (aprobadas,
        // rechazadas y las que pasaron por algún cambio). La bandeja de entrada
        // solo muestra pendientes sin resolver, así que este listado es su
        // complemento: dónde quedó cada trámite ya tocado.
        Route::prefix('tratadas')->name('admin.tratadas.')->group(function () {
            Route::get('/', [AdminTratadasController::class, 'index'])->name('index');
            Route::get('/{solicitud}', [AdminTratadasController::class, 'show'])->name('show');
        });

        Route::resource('requisitos', AdminRequisitoController::class)->names('admin.requisitos');

        Route::resource('tipos-solicitud', AdminTipoSolicitudController::class)
            ->except(['show'])
            ->names('admin.tipos-solicitud');

        Route::patch('/tipos-solicitud/{id}/alternar-estado', [AdminTipoSolicitudController::class, 'alternarEstado'])
            ->name('admin.tipos-solicitud.alternar-estado');

        // Preguntas frecuentes: CRUD propio del administrador. Antes compartia una
        // vista con el estudiante que se bifurcaba por rol, y create()/edit()
        // apuntaban a vistas que no existen.
        // Sin 'show': la pregunta se lee dentro del listado o del portal del
        // estudiante, no en una pagina propia.
        Route::resource('preguntas', AdminPreguntaFrecuenteController::class)
            ->except(['show'])
            ->names('admin.preguntas');
    });

    // Gestión de usuarios: solo el administrador
    Route::middleware('rol:administrador')->prefix('usuarios')->name('admin.usuarios.')->group(function () {
        Route::get('/', [AdminUserController::class, 'index'])->name('index');
        Route::get('/buscar', [AdminUserController::class, 'search'])->name('search'); // AJAX
        Route::post('/agregar', [AdminUserController::class, 'agregarPorCorreo'])->name('agregar');
        Route::put('/{usuario}', [AdminUserController::class, 'update'])->name('update');
        Route::post('/{usuario}/desbloquear', [AdminUserController::class, 'unlock'])->name('unlock');
        Route::delete('/{usuario}', [AdminUserController::class, 'destroy'])->name('destroy');
    });

    // Datos de precarga del estudiante: qué campos se piden (la "versión" que
    // el admin edita) y qué tiene cargado cada estudiante, con filtros para
    // ubicar quién está incompleto o desactualizado. Ambos módulos son del
    // administrador porque afectan la forma en que se reciben los trámites.
    Route::middleware('rol:administrador')->group(function () {
        Route::prefix('datos-precarga')->name('admin.datos-precarga.')->group(function () {
            Route::get('/', [AdminDatosPrecargaController::class, 'index'])->name('index');
            Route::get('/agregar', [AdminDatosPrecargaController::class, 'create'])->name('create');
            Route::post('/', [AdminDatosPrecargaController::class, 'store'])->name('store');
            Route::put('/{config}', [AdminDatosPrecargaController::class, 'update'])->name('update');
        });

        Route::prefix('datos-estudiantes')->name('admin.datos-estudiantes.')->group(function () {
            Route::get('/', [AdminDatosEstudiantesController::class, 'index'])->name('index');
        });
    });

    // ---- Los tres roles administrativos ----

    // Cola de solicitudes: procesar, aprobar y rechazar. Es la página de
    // inicio del taquillero, que no puede abrir el panel estadístico.
    Route::get('/solicitudes', [AdminSolicitudController::class, 'index'])->name('admin.solicitudes.index');

    // Resolver una solicitud. El POST es el camino real: lleva token CSRF y es lo
    // único que permite mandar la observación (un GET la metería en la URL y la
    // truncaría). El GET se conserva como alias para los enlaces que ya había,
    // pero en ambos la acción va restringida a aprobar|rechazar: sin esa
    // restricción, cualquier texto distinto de "aprobar" caía callado en la
    // rama del rechazo.
    Route::post('/dashboard/estado/{id}/{accion}', [AdminDashboardController::class, 'cambiarEstado'])
        ->where('accion', 'aprobar|rechazar')
        ->name('admin.dashboard.estado.store');

    Route::get('/dashboard/estado/{id}/{accion}', [AdminDashboardController::class, 'cambiarEstado'])
        ->where('accion', 'aprobar|rechazar')
        ->name('admin.dashboard.estado');

    // Chats de soporte. El middleware 'admin' ya exige sesion, asi que no hace
    // falta volver a anadir 'auth' aqui.
    Route::prefix('chat')->name('admin.chat.')->group(function () {
        Route::get('/', [SoporteController::class, 'verHistorial'])->name('index');
        Route::get('/{id}', [SoporteController::class, 'mostrarChat'])->name('mostrar');
        Route::post('/{id}/mensaje', [SoporteController::class, 'enviarMensaje'])->name('enviar');
        Route::post('/{id}/reclamar', [SoporteController::class, 'reclamarChat'])->name('reclamar');
        // Con guion, igual que admin.tipos-solicitud.alternar-estado: era el
        // único nombre de ruta con guion bajo y se colaba con la referencia.
        Route::post('/{id}/proponer-cierre', [SoporteController::class, 'proponerCierre'])->name('proponer-cierre');
    });
});
