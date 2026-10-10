<?php

use App\Models\ChatSoporte\HiloChat;
use App\Models\ChatSoporte\MensajeChat;
use App\Models\TipoDocumento;
use App\Models\TipoSolicitud;
use App\Models\Usuario;
use App\Services\SoporteService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Escalado del chat de soporte por niveles y cierre forzado por conducta
 * inadecuada, más la desactivación inmediata de trámites para el estudiante.
 */
function escaladoUsuario(string $rol, string $numeroDocumento): Usuario
{
    $documento = TipoDocumento::firstOrCreate(
        ['tdo_abreviatura' => 'V'],
        ['tdo_nombre_documento' => 'Cédula de Identidad'],
    );

    return Usuario::create([
        'usu_rol' => $rol,
        'usu_tdo_id' => $documento->tdo_id,
        'usu_primer_nombre' => 'Persona',
        'usu_primer_apellido' => $rol,
        'usu_numero_documento' => $numeroDocumento,
        'usu_correo_electronico' => $numeroDocumento.'@ejemplo.com',
        'usu_contrasena_hash' => bcrypt('password'),
        'usu_estado_cuenta' => 'activo',
        'usu_fecha_registro' => now(),
    ]);
}

function escaladoHilo(Usuario $estudiante, string $estado = 'pendiente', ?Usuario $admin = null, int $nivel = 1): HiloChat
{
    return HiloChat::create([
        'hch_id_usuario' => $estudiante->usu_id,
        'hch_id_admin' => $admin?->usu_id,
        'hch_estado' => $estado,
        'hch_nivel_atencion' => $nivel,
    ]);
}

/*
|--------------------------------------------------------------------------
| Escalado de chat por niveles
|--------------------------------------------------------------------------
*/

test('el estudiante escala su chat y vuelve a la bandeja del siguiente nivel', function () {
    $estudiante = escaladoUsuario('estudiante', 'V-30000001');
    $hilo = escaladoHilo($estudiante, 'pendiente');

    $this->actingAs($estudiante)
        ->post(route('user.ayuda.chat.escalar', $hilo->hch_id))
        ->assertRedirect();

    $hilo->refresh();

    // El chat queda en estado pending_escalation, esperando aprobacion del admin.
    // El nivel no cambia hasta que el admin apruebe.
    expect($hilo->hch_estado)->toBe('pending_escalation')
        ->and($hilo->hch_nivel_atencion)->toBe(1)
        ->and($hilo->hch_id_admin)->toBeNull();

    // La solicitud de escalado queda registrada en la conversacion.
    $this->assertTrue(
        MensajeChat::where('mch_id_hilo', $hilo->hch_id)
            ->where('mch_cuerpo', 'like', '%Solicitud de escalado%')
            ->exists()
    );
});

test('el personal que no puede resolver la consulta también puede escalarla', function () {
    $estudiante = escaladoUsuario('estudiante', 'V-30000001');
    $taquillero = escaladoUsuario('taquillero', 'V-30000002');
    $hilo = escaladoHilo($estudiante, 'activo', $taquillero, 1);

    $this->actingAs($taquillero)
        ->post(route('admin.chat.escalar', $hilo->hch_id))
        ->assertRedirect(route('admin.chat.index'));

    $hilo->refresh();

    // El chat queda en estado pending_escalation, esperando aprobacion del admin.
    // El nivel no cambia hasta que el admin apruebe. El admin que lo atendia
    // sigue registrado (no se limpia al solo solicitar, eso pasa en aprobar).
    expect($hilo->hch_estado)->toBe('pending_escalation')
        ->and($hilo->hch_nivel_atencion)->toBe(1)
        ->and((int) $hilo->hch_id_admin === (int) $taquillero->usu_id);
});

test('un chat escalado al analista ya no aparece en la bandeja del taquillero', function () {
    $estudiante = escaladoUsuario('estudiante', 'V-30000001');
    $taquillero = escaladoUsuario('taquillero', 'V-30000002');
    $analista = escaladoUsuario('analista', 'V-30000003');
    $administrador = escaladoUsuario('administrador', 'V-30000004');

    // Un chat en nivel 1 y otro ya escalado a nivel 2.
    escaladoHilo($estudiante, 'pendiente', null, 1);
    escaladoHilo($estudiante, 'pendiente', null, 2);

    $bandejaTaquillero = app(SoporteService::class)->obtenerEstadoBandejaSoporte($taquillero);
    $bandejaAnalista = app(SoporteService::class)->obtenerEstadoBandejaSoporte($analista);
    $bandejaAdmin = app(SoporteService::class)->obtenerEstadoBandejaSoporte($administrador);

    expect($bandejaTaquillero['hilosPendientes'])->toHaveCount(1)
        ->and((int) $bandejaTaquillero['hilosPendientes']->first()->hch_nivel_atencion)->toBe(1)
        ->and($bandejaAnalista['hilosPendientes'])->toHaveCount(2)
        ->and($bandejaAdmin['hilosPendientes'])->toHaveCount(2);
});

test('un chat en el nivel máximo ya no puede escalar', function () {
    $estudiante = escaladoUsuario('estudiante', 'V-30000001');
    $hilo = escaladoHilo($estudiante, 'activo', null, 3);

    expect($hilo->puedeEscalar())->toBeFalse();

    $this->actingAs($estudiante)
        ->post(route('user.ayuda.chat.escalar', $hilo->hch_id))
        ->assertRedirect();

    $hilo->refresh();

    expect($hilo->hch_nivel_atencion)->toBe(3);
});

test('un estudiante no puede escalar el chat ajeno', function () {
    $dueño = escaladoUsuario('estudiante', 'V-30000001');
    $ajeno = escaladoUsuario('estudiante', 'V-30000002');
    $hilo = escaladoHilo($dueño, 'pendiente');

    $this->actingAs($ajeno)
        ->post(route('user.ayuda.chat.escalar', $hilo->hch_id))
        ->assertRedirect();

    $hilo->refresh();

    expect($hilo->hch_nivel_atencion)->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Cierre forzado por conducta inadecuada
|--------------------------------------------------------------------------
*/

test('el administrador cierra un chat de forma forzada y el motivo queda en la conversación', function () {
    $estudiante = escaladoUsuario('estudiante', 'V-30000001');
    $taquillero = escaladoUsuario('taquillero', 'V-30000002');
    $administrador = escaladoUsuario('administrador', 'V-30000003');
    $hilo = escaladoHilo($estudiante, 'activo', $taquillero);

    $this->actingAs($administrador)
        ->post(route('admin.chat.cerrar-forzado', $hilo->hch_id), [
            'motivo' => 'Lenguaje inapropiado hacia el personal.',
        ])
        ->assertRedirect();

    $hilo->refresh();

    expect($hilo->hch_estado)->toBe('cerrado')
        ->and($hilo->hch_etiqueta_tema)->toBe('Cierre forzado');

    $this->assertTrue(
        MensajeChat::where('mch_id_hilo', $hilo->hch_id)
            ->where('mch_cuerpo', 'like', '%Lenguaje inapropiado%')
            ->exists()
    );
});

test('un analista no puede aplicar el cierre forzado', function () {
    $estudiante = escaladoUsuario('estudiante', 'V-30000001');
    $analista = escaladoUsuario('analista', 'V-30000002');
    $hilo = escaladoHilo($estudiante, 'activo', null);

    $this->actingAs($analista)
        ->post(route('admin.chat.cerrar-forzado', $hilo->hch_id), [
            'motivo' => 'Cualquier motivo.',
        ])
        ->assertForbidden();

    $hilo->refresh();

    expect($hilo->hch_estado)->toBe('activo');
});

test('el cierre forzado exige un motivo', function () {
    $estudiante = escaladoUsuario('estudiante', 'V-30000001');
    $administrador = escaladoUsuario('administrador', 'V-30000002');
    $hilo = escaladoHilo($estudiante, 'activo', null);

    $this->actingAs($administrador)
        ->post(route('admin.chat.cerrar-forzado', $hilo->hch_id), [
            'motivo' => '',
        ])
        ->assertInvalid('motivo');

    $hilo->refresh();

    expect($hilo->hch_estado)->toBe('activo');
});

/*
|--------------------------------------------------------------------------
| Desactivación de trámites
|--------------------------------------------------------------------------
*/

test('un trámite desactivado desaparece del catálogo del estudiante y no admite solicitudes', function () {
    $estudiante = escaladoUsuario('estudiante', 'V-30000001');

    TipoDocumento::firstOrCreate(
        ['tdo_abreviatura' => 'V'],
        ['tdo_nombre_documento' => 'Cédula de Identidad'],
    );

    $tramite = TipoSolicitud::firstOrCreate(
        ['tsi_nombre_tipo' => 'Constancia de estudio'],
        ['tsi_estado_tipo' => 'activo', 'tsi_tiempo_estimado_dias' => 5],
    );

    // Mientras está activo, el estudiante lo ve y puede llegar al formulario.
    $this->actingAs($estudiante)
        ->get(route('user.tramites.index'))
        ->assertOk()
        ->assertSee($tramite->tsi_nombre_tipo);

    $this->get(route('user.tramites.solicitar', $tramite->tsi_id))->assertOk();

    // El administrador lo marca inactivo.
    $tramite->update(['tsi_estado_tipo' => 'inactivo']);

    // Desaparece del listado en la siguiente carga y el formulario directo
    // rebotar a la bandeja con error: no queda ninguna vía para solicitarlo.
    $this->actingAs($estudiante)
        ->get(route('user.tramites.index'))
        ->assertOk()
        ->assertDontSee($tramite->tsi_nombre_tipo);

    $this->actingAs($estudiante)
        ->get(route('user.tramites.solicitar', $tramite->tsi_id))
        ->assertRedirect(route('dashboard'));
});
