@extends('layouts.plantilla_admin')

@section('title', 'Configuración de datos de precarga')

@section('content')
    @include('partials.miga', ['miga' => [['texto' => 'Administración'], ['texto' => 'Datos de precarga'], ['texto' => 'Configurar campos']]])

    @include('partials.avisos')

    <div class="pagina-head">
        <div>
            <h1 class="pagina-head__titulo">Configurar campos de precarga</h1>
            <p class="pagina-head__desc">
                Define la versión de los datos que los estudiantes pueden precargar: qué campos se
                piden en su perfil, cuáles son obligatorios y cada cuántos meses pierden vigencia.
                El formulario del estudiante se arma con esta tabla, así que los cambios aplican de
                inmediato y quedan anotados en la bitácora.
            </p>
        </div>

        <div class="pagina-head__acciones">
            <a href="{{ route('admin.datos-precarga.create') }}" class="btn btn--primary">Agregar campo</a>
            <a href="{{ route('admin.datos-estudiantes.index') }}" class="btn btn--ghost">Ver datos de estudiantes</a>
        </div>
    </div>

    <div class="card">
        @if ($campos->isEmpty())
            <p class="estado-vacio">
                Todavía no hay campos configurados.
                <br>
                Ejecuta el seeder de configuración de precarga o agrega los campos por consola.
            </p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Campo</th>
                            <th>Estado</th>
                            <th>Ajustes</th>
                            <th class="table__acciones">Guardar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($campos as $config)
                            <tr>
                                <form method="POST"
                                      action="{{ route('admin.datos-precarga.update', $config) }}">
                                    @csrf
                                    @method('PUT')

                                    <td>
                                        <strong>{{ $config->dpc_etiqueta }}</strong>
                                    </td>

                                    <td>
                                        @if ($config->dpc_activo)
                                            <span class="badge badge--activo">Habilitado</span>
                                        @else
                                            <span class="badge badge--rechazada">Deshabilitado</span>
                                        @endif

                                        @if ($config->dpc_obligatorio)
                                            <span class="badge badge--pendiente">Obligatorio</span>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="form__row" style="grid-template-columns: 1fr 1fr; gap: 12px;">
                                            <div class="field">
                                                <label for="etiqueta-{{ $config->dpc_id }}">Etiqueta visible</label>
                                                <input type="text" id="etiqueta-{{ $config->dpc_id }}"
                                                       name="dpc_etiqueta" maxlength="100" required
                                                       value="{{ old('dpc_etiqueta', $config->dpc_etiqueta) }}">
                                            </div>

                                            <div class="field">
                                                <label for="vigencia-{{ $config->dpc_id }}">Vigencia</label>
                                                <select id="vigencia-{{ $config->dpc_id }}" name="dpc_vigencia_meses">
                                                    <option value="" @selected($config->dpc_vigencia_meses === null)>No vence</option>
                                                    @foreach ([3, 6, 12, 24] as $meses)
                                                        <option value="{{ $meses }}" @selected((int) old('dpc_vigencia_meses', $config->dpc_vigencia_meses) === $meses)>
                                                            {{ $meses }} meses
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="field">
                                                <label for="obligatorio-{{ $config->dpc_id }}">El estudiante debe llenarlo</label>
                                                <select id="obligatorio-{{ $config->dpc_id }}" name="dpc_obligatorio">
                                                    <option value="1" @selected($config->dpc_obligatorio)>Obligatorio</option>
                                                    <option value="0" @selected(! $config->dpc_obligatorio)>Opcional</option>
                                                </select>
                                            </div>

                                            <div class="field">
                                                <label for="activo-{{ $config->dpc_id }}">Se pide en el perfil</label>
                                                <select id="activo-{{ $config->dpc_id }}" name="dpc_activo">
                                                    <option value="1" @selected($config->dpc_activo)>Habilitado</option>
                                                    <option value="0" @selected(! $config->dpc_activo)>Deshabilitado</option>
                                                </select>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="table__acciones">
                                        <button type="submit" class="btn btn--primary btn--sm">Guardar</button>
                                    </td>
                                </form>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="alert alert--info">
        <strong>Cómo funciona la vigencia:</strong> los datos de precarga comparten una misma fecha de
        actualización (la del último guardado del estudiante). Un campo con vigencia de 6 meses hace que
        el sistema avise al estudiante cuando pasan 6 meses sin actualizar, y que el panel de
        <a href="{{ route('admin.datos-estudiantes.index') }}">datos de estudiantes</a> lo marque como desactualizado.
    </div>
@endsection
