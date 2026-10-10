<?php

use App\Models\DatosPrecargaConfig;
use App\Models\HistorialCambio;
use App\Models\TipoDocumento;
use App\Models\TipoSolicitud;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Catálogo mínimo: tipo de documento y dos campos de precarga activos
 * (uno obligatorio con vigencia, otro opcional permanente) más uno
 * deshabilitado para comprobar que no se muestra.
 */
function datosPrecargaCatalogo(): void
{
    TipoDocumento::firstOrCreate(
        ['tdo_abreviatura' => 'V'],
        ['tdo_nombre_documento' => 'Cédula de identidad'],
    );

    DatosPrecargaConfig::firstOrCreate(
        ['dpc_campo' => 'usu_direccion'],
        ['dpc_etiqueta' => 'Dirección', 'dpc_obligatorio' => true, 'dpc_activo' => true, 'dpc_vigencia_meses' => 6],
    );

    DatosPrecargaConfig::firstOrCreate(
        ['dpc_campo' => 'usu_fecha_nacimiento'],
        ['dpc_etiqueta' => 'Fecha de nacimiento', 'dpc_obligatorio' => false, 'dpc_activo' => true, 'dpc_vigencia_meses' => null],
    );

    DatosPrecargaConfig::firstOrCreate(
        ['dpc_campo' => 'usu_lugar_nacimiento'],
        ['dpc_etiqueta' => 'Lugar de nacimiento', 'dpc_obligatorio' => false, 'dpc_activo' => false, 'dpc_vigencia_meses' => null],
    );
}

function datosPrecargaUsuario(string $rol = 'estudiante', string $documento = 'V-40000001'): Usuario
{
    datosPrecargaCatalogo();

    return Usuario::create([
        'usu_rol' => $rol,
        'usu_tdo_id' => TipoDocumento::where('tdo_abreviatura', 'V')->firstOrFail()->tdo_id,
        'usu_primer_nombre' => 'Ana',
        'usu_primer_apellido' => 'Pérez',
        'usu_numero_documento' => $documento,
        'usu_correo_electronico' => mb_strtolower($documento).'@ejemplo.com',
        'usu_contrasena_hash' => bcrypt('password'),
        'usu_estado_cuenta' => 'activo',
        'usu_fecha_registro' => now(),
    ]);
}

/**
 * Carga válida del formulario del estudiante.
 *
 * @return array<string, string>
 */
function datosPrecargaFormularioValido(): array
{
    return [
        'usu_primer_nombre' => 'Ana',
        'usu_segundo_nombre' => '',
        'usu_primer_apellido' => 'Pérez',
        'usu_segundo_apellido' => '',
        'usu_correo_electronico' => 'v-40000001@ejemplo.com',
        'usu_direccion' => 'Av. 4 con calle 5',
        'usu_fecha_nacimiento' => '2000-05-14',
    ];
}

/*
|--------------------------------------------------------------------------
| Formulario del estudiante
|--------------------------------------------------------------------------
*/

test('el estudiante ve los campos habilitados por el admin y no los deshabilitados', function () {
    $estudiante = datosPrecargaUsuario();

    $this->actingAs($estudiante)
        ->get(route('user.datos-perfil'))
        ->assertOk()
        ->assertSee('Dirección')
        ->assertSee('Obligatorio')
        ->assertSee('Fecha de nacimiento')
        ->assertDontSee('Lugar de nacimiento');
});

test('el estudiante guarda sus datos y queda reflejado en la bitácora', function () {
    $estudiante = datosPrecargaUsuario();

    $this->actingAs($estudiante)
        ->from(route('user.datos-perfil'))
        ->post(route('user.datos-perfil.update'), datosPrecargaFormularioValido())
        ->assertRedirect(route('user.datos-perfil'));

    $estudiante->refresh();

    expect($estudiante->usu_direccion)->toBe('Av. 4 con calle 5')
        ->and($estudiante->usu_fecha_nacimiento)->toBe('2000-05-14')
        ->and($estudiante->usu_datos_ultima_actualizacion)->not->toBeNull();

    $this->assertDatabaseHas('historial_cambios', [
        'hcm_entidad' => 'Usuario',
        'hcm_entidad_id' => $estudiante->usu_id,
        'hcm_usu_id' => $estudiante->usu_id,
        'hcm_accion' => 'actualizo',
        'hcm_resumen' => 'Actualizó sus datos personales (precarga)',
    ]);
});

test('la fecha de nacimiento futura se rechaza', function () {
    $estudiante = datosPrecargaUsuario();

    $this->actingAs($estudiante)
        ->post(route('user.datos-perfil.update'), array_merge(
            datosPrecargaFormularioValido(),
            ['usu_fecha_nacimiento' => '2150-01-01'],
        ))
        ->assertInvalid('usu_fecha_nacimiento');
});

test('el campo obligatorio vacío se rechaza', function () {
    $estudiante = datosPrecargaUsuario();

    $this->actingAs($estudiante)
        ->post(route('user.datos-perfil.update'), array_merge(
            datosPrecargaFormularioValido(),
            ['usu_direccion' => ''],
        ))
        ->assertInvalid('usu_direccion');
});

test('guardar sin cambios no escribe en la bitácora', function () {
    $estudiante = datosPrecargaUsuario();
    $estudiante->update([
        'usu_direccion' => 'Av. 4 con calle 5',
        'usu_fecha_nacimiento' => '2000-05-14',
    ]);

    $this->actingAs($estudiante)
        ->from(route('user.datos-perfil'))
        ->post(route('user.datos-perfil.update'), datosPrecargaFormularioValido())
        ->assertRedirect(route('user.datos-perfil'));

    expect(HistorialCambio::where('hcm_entidad', 'Usuario')->count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Configuración del administrador
|--------------------------------------------------------------------------
*/

test('el admin edita la vigencia de un dato y queda reflejado en la bitácora', function () {
    $admin = datosPrecargaUsuario('administrador', 'V-40000002');
    $campo = DatosPrecargaConfig::where('dpc_campo', 'usu_direccion')->firstOrFail();

    $this->actingAs($admin)
        ->put(route('admin.datos-precarga.update', $campo), [
            'dpc_etiqueta' => 'Dirección de habitación',
            'dpc_obligatorio' => '1',
            'dpc_activo' => '1',
            'dpc_vigencia_meses' => '12',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('datos_precarga_config', [
        'dpc_id' => $campo->dpc_id,
        'dpc_etiqueta' => 'Dirección de habitación',
        'dpc_vigencia_meses' => 12,
    ]);

    $this->assertDatabaseHas('historial_cambios', [
        'hcm_entidad' => 'DatosPrecargaConfig',
        'hcm_entidad_id' => $campo->dpc_id,
        'hcm_accion' => 'actualizo',
        'hcm_usu_id' => $admin->usu_id,
    ]);
});

test('el admin puede poner un dato con vigencia sin vencimiento', function () {
    $admin = datosPrecargaUsuario('administrador', 'V-40000002');
    $campo = DatosPrecargaConfig::where('dpc_campo', 'usu_direccion')->firstOrFail();

    $this->actingAs($admin)
        ->put(route('admin.datos-precarga.update', $campo), [
            'dpc_etiqueta' => 'Dirección',
            'dpc_obligatorio' => '1',
            'dpc_activo' => '1',
            'dpc_vigencia_meses' => '',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('datos_precarga_config', [
        'dpc_id' => $campo->dpc_id,
        'dpc_vigencia_meses' => null,
    ]);
});

test('el admin agrega un campo de precarga nuevo y el estudiante lo ve', function () {
    $admin = datosPrecargaUsuario('administrador', 'V-40000002');
    $estudiante = datosPrecargaUsuario('estudiante', 'V-40000003');

    // El alta vive en su propia ventana, apartada del listado.
    $this->actingAs($admin)
        ->get(route('admin.datos-precarga.create'))
        ->assertOk()
        ->assertSee('PNF / carrera');

    $this->actingAs($admin)
        ->post(route('admin.datos-precarga.store'), [
            'dpc_campo' => 'usu_pnf',
            'dpc_etiqueta' => 'PNF / carrera',
            'dpc_obligatorio' => '0',
            'dpc_activo' => '1',
            'dpc_vigencia_meses' => '',
        ])
        ->assertRedirect(route('admin.datos-precarga.index'));

    $this->assertDatabaseHas('datos_precarga_config', [
        'dpc_campo' => 'usu_pnf',
        'dpc_etiqueta' => 'PNF / carrera',
        'dpc_activo' => true,
    ]);

    $this->assertDatabaseHas('historial_cambios', [
        'hcm_entidad' => 'DatosPrecargaConfig',
        'hcm_accion' => 'creo',
        'hcm_usu_id' => $admin->usu_id,
    ]);

    $this->actingAs($estudiante)
        ->get(route('user.datos-perfil'))
        ->assertOk()
        ->assertSee('PNF / carrera');
});

test('el admin no puede agregar una columna fuera de las disponibles', function () {
    $admin = datosPrecargaUsuario('administrador', 'V-40000002');

    $this->actingAs($admin)
        ->post(route('admin.datos-precarga.store'), [
            'dpc_campo' => 'usu_correo_electronico',
            'dpc_etiqueta' => 'Correo',
            'dpc_obligatorio' => '0',
            'dpc_activo' => '1',
            'dpc_vigencia_meses' => '',
        ])
        ->assertInvalid('dpc_campo');

    $this->assertDatabaseMissing('datos_precarga_config', [
        'dpc_campo' => 'usu_correo_electronico',
    ]);
});

/*
|--------------------------------------------------------------------------
| Panel de datos de estudiantes
|--------------------------------------------------------------------------
*/

test('el admin filtra el panel por completitud de datos', function () {
    $admin = datosPrecargaUsuario('administrador', 'V-40000002');

    $completo = datosPrecargaUsuario('estudiante', 'V-40000003');
    $completo->update([
        'usu_direccion' => 'Av. 4 con calle 5',
        'usu_fecha_nacimiento' => '2000-05-14',
        'usu_datos_ultima_actualizacion' => now(),
    ]);

    datosPrecargaUsuario('estudiante', 'V-40000004');

    $this->actingAs($admin)
        ->get(route('admin.datos-estudiantes.index', ['datos' => 'completos']))
        ->assertOk()
        ->assertSee($completo->nombre_completo)
        ->assertDontSee('V-40000004');

    $this->actingAs($admin)
        ->get(route('admin.datos-estudiantes.index', ['datos' => 'sin-datos']))
        ->assertOk()
        ->assertSee('V-40000004')
        ->assertDontSee($completo->usu_numero_documento);
});

test('el admin filtra el panel por vigencia de datos', function () {
    $admin = datosPrecargaUsuario('administrador', 'V-40000002');

    // Vigente: actualizó hoy (la vigencia mínima del catálogo son 6 meses).
    $vigente = datosPrecargaUsuario('estudiante', 'V-40000003');
    $vigente->update([
        'usu_direccion' => 'Av. 4 con calle 5',
        'usu_fecha_nacimiento' => '2000-05-14',
        'usu_datos_ultima_actualizacion' => now(),
    ]);

    // Desactualizado: llenó los datos hace un año.
    $vencido = datosPrecargaUsuario('estudiante', 'V-40000004');
    $vencido->update([
        'usu_direccion' => 'Calle 9',
        'usu_fecha_nacimiento' => '1999-01-02',
        'usu_datos_ultima_actualizacion' => now()->subYear(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.datos-estudiantes.index', ['vigencia' => 'desactualizados']))
        ->assertOk()
        ->assertSee($vencido->usu_numero_documento)
        ->assertDontSee($vigente->usu_numero_documento);
});

test('el panel de datos de estudiantes queda fuera del alcance del analista y del estudiante', function () {
    $analista = datosPrecargaUsuario('analista', 'V-40000003');
    $estudiante = datosPrecargaUsuario('estudiante', 'V-40000004');

    $this->actingAs($analista)
        ->get(route('admin.datos-precarga.index'))
        ->assertForbidden();

    $this->actingAs($analista)
        ->get(route('admin.datos-estudiantes.index'))
        ->assertForbidden();

    $this->actingAs($estudiante)
        ->get(route('admin.datos-estudiantes.index'))
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Precarga en el trámite
|--------------------------------------------------------------------------
*/

test('el formulario del trámite muestra los datos que se adjuntarán', function () {
    $estudiante = datosPrecargaUsuario();
    $estudiante->update([
        'usu_direccion' => 'Av. 4 con calle 5',
        'usu_fecha_nacimiento' => '2000-05-14',
        'usu_datos_ultima_actualizacion' => now(),
    ]);

    $tramite = TipoSolicitud::firstOrCreate(
        ['tsi_nombre_tipo' => 'Constancia de estudio'],
        ['tsi_estado_tipo' => 'activo', 'tsi_tiempo_estimado_dias' => 5],
    );

    $this->actingAs($estudiante)
        ->get(route('user.tramites.solicitar', $tramite->tsi_id))
        ->assertOk()
        ->assertSee('Av. 4 con calle 5')
        ->assertSee('2000-05-14');
});

test('el trámite avisa cuando falta un dato obligatorio de la precarga', function () {
    $estudiante = datosPrecargaUsuario();
    $tramite = TipoSolicitud::firstOrCreate(
        ['tsi_nombre_tipo' => 'Constancia de estudio'],
        ['tsi_estado_tipo' => 'activo', 'tsi_tiempo_estimado_dias' => 5],
    );

    $this->actingAs($estudiante)
        ->get(route('user.tramites.solicitar', $tramite->tsi_id))
        ->assertOk()
        ->assertSee('Dirección');
});
