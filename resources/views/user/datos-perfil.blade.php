@extends('layouts.plantilla_user')

@section('title', 'Mis datos')

@section('content')
    @include('partials.avisos')

    @include('partials.miga', [
        'miga' => [
            ['texto' => 'Inicio', 'ruta' => route('dashboard')],
            ['texto' => 'Mis datos'],
        ],
    ])

    <div class="pagina-head">
        <div>
            <h1 class="pagina-head__titulo">Mis datos</h1>
            <p class="pagina-head__desc">
                Cárgalos una sola vez y se adjuntarán solos a tus trámites:
                no volverás a escribirlos en cada solicitud.
            </p>
        </div>

        <div class="pagina-head__acciones">
            <a href="{{ route('dashboard') }}" class="btn btn--ghost">Volver al panel</a>
        </div>
    </div>

    {{-- Aviso de vigencia: los datos que el admin marcó con caducidad hay que
         refrescarlos de vez en cuando; los que nunca actualizó, llenarlos. --}}
    @if ($ultimaActualizacion === null)
        <div class="alert alert--warning">
            Todavía no has cargado tus datos de precarga. Complétalos aquí para que tus
            próximos trámites los adjunten automáticamente.
        </div>
    @elseif ($vencidos->isNotEmpty())
        <div class="alert alert--warning">
            Según la vigencia definida por la administración, estos datos ya perdieron validez:
            <strong>{{ $vencidos->pluck('dpc_etiqueta')->implode(', ') }}</strong>.
            Actualízalos para que tus trámites no salgan con datos desactualizados.
        </div>
    @endif

    <form action="{{ route('user.datos-perfil.update') }}" method="POST">
        @csrf

        {{-- Datos básicos de la cuenta: siempre se pueden corregir --}}
        <div class="card" style="margin-bottom: 20px;">
            <h2 class="card__title">Datos de tu cuenta</h2>
            <p class="card__sub">Con ellos te identifica el sistema y te contacta la oficina.</p>

            <div class="form__row" style="margin-top: 16px;">
                <div class="field">
                    <label for="usu_primer_nombre">Primer nombre</label>
                    <input type="text" id="usu_primer_nombre" name="usu_primer_nombre" required
                           maxlength="50" value="{{ old('usu_primer_nombre', $usuario->usu_primer_nombre) }}">
                </div>
                <div class="field">
                    <label for="usu_segundo_nombre">Segundo nombre</label>
                    <input type="text" id="usu_segundo_nombre" name="usu_segundo_nombre"
                           maxlength="50" value="{{ old('usu_segundo_nombre', $usuario->usu_segundo_nombre) }}">
                </div>
            </div>

            <div class="form__row">
                <div class="field">
                    <label for="usu_primer_apellido">Primer apellido</label>
                    <input type="text" id="usu_primer_apellido" name="usu_primer_apellido" required
                           maxlength="50" value="{{ old('usu_primer_apellido', $usuario->usu_primer_apellido) }}">
                </div>
                <div class="field">
                    <label for="usu_segundo_apellido">Segundo apellido</label>
                    <input type="text" id="usu_segundo_apellido" name="usu_segundo_apellido"
                           maxlength="50" value="{{ old('usu_segundo_apellido', $usuario->usu_segundo_apellido) }}">
                </div>
            </div>

            <div class="form__row">
                <div class="field">
                    <label for="usu_correo_electronico">Correo electrónico</label>
                    <input type="email" id="usu_correo_electronico" name="usu_correo_electronico" required
                           maxlength="100" value="{{ old('usu_correo_electronico', $usuario->usu_correo_electronico) }}">
                    <span class="field__hint">Es tu usuario de acceso: si lo cambias, usa el nuevo la próxima vez.</span>
                </div>
            </div>
        </div>

        {{-- Datos de precarga: los que el administrador habilitó --}}
        <div class="card" style="margin-bottom: 20px;">
            <h2 class="card__title">Datos que se precargan en tus trámites</h2>
            <p class="card__sub">
                El administrador decide qué datos se piden.
                @if ($ultimaActualizacion)
                    Última actualización: <strong>{{ $ultimaActualizacion->format('d/m/Y H:i') }}</strong>.
                @endif
            </p>

            @if ($campos->isEmpty())
                <p class="estado-vacio" style="text-align: left;">
                    La administración todavía no ha habilitado ningún dato de precarga.
                </p>
            @else
                <div class="form__row" style="margin-top: 16px;">
                    @foreach ($campos as $config)
                        <div class="field">
                            <label for="{{ $config->dpc_campo }}">
                                {{ $config->dpc_etiqueta }}
                                @if ($config->dpc_obligatorio)
                                    <span class="badge badge--rechazada">Obligatorio</span>
                                @else
                                    <span class="badge badge--activo">Opcional</span>
                                @endif
                            </label>

                            @if ($config->dpc_campo === 'usu_fecha_nacimiento')
                                <input type="date" id="{{ $config->dpc_campo }}" name="{{ $config->dpc_campo }}"
                                       @required($config->dpc_obligatorio)
                                       value="{{ old($config->dpc_campo, $usuario->{$config->dpc_campo}) }}">
                            @else
                                <input type="text" id="{{ $config->dpc_campo }}" name="{{ $config->dpc_campo }}"
                                       @required($config->dpc_obligatorio) maxlength="255"
                                       value="{{ old($config->dpc_campo, $usuario->{$config->dpc_campo}) }}">
                            @endif

                            <span class="field__hint">
                                @if ($config->dpc_vigencia_meses !== null)
                                    Vigente durante {{ $config->dpc_vigencia_meses }} meses tras cada actualización.
                                @else
                                    No pierde vigencia.
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="actions">
            <button type="submit" class="btn btn--primary">Guardar cambios</button>
            <a href="{{ route('dashboard') }}" class="btn btn--ghost">Cancelar</a>
        </div>
    </form>
@endsection
