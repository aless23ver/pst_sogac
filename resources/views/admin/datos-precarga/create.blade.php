@extends('layouts.plantilla_admin')

@section('title', 'Agregar campo de precarga')

@section('content')
    @include('partials.miga', ['miga' => [
        ['texto' => 'Administración'],
        ['texto' => 'Datos de precarga', 'ruta' => route('admin.datos-precarga.index')],
        ['texto' => 'Agregar campo'],
    ]])

    @include('partials.avisos')

    <div class="pagina-head">
        <div>
            <h1 class="pagina-head__titulo">Agregar campo de precarga</h1>
            <p class="pagina-head__desc">
                Habilita un dato nuevo para que los estudiantes lo carguen en su perfil y se
                adjunte a sus trámites. Elige el dato, cómo se llamará y cada cuánto vence.
            </p>
        </div>

        <div class="pagina-head__acciones">
            <a href="{{ route('admin.datos-precarga.index') }}" class="btn btn--ghost">Volver al listado</a>
        </div>
    </div>

    <div class="card" style="max-width: 760px;">
        @if (empty($columnasDisponibles))
            <p class="estado-vacio">
                Todos los datos disponibles para precarga ya están configurados.
                <br>
                <a href="{{ route('admin.datos-precarga.index') }}">Revisa el listado</a>:
                un dato nuevo requiere que exista en la base del sistema, así que si hace
                falta otro, el personal técnico lo agrega como columna y aparecerá aquí.
            </p>
        @else
            <form method="POST" action="{{ route('admin.datos-precarga.store') }}">
                @csrf

                <div class="form__row" style="margin-top: 16px;">
                    <div class="field">
                        <label for="nuevo_campo">Dato a precargar</label>
                        <select id="nuevo_campo" name="dpc_campo" required>
                            @foreach ($columnasDisponibles as $columna => $etiqueta)
                                <option value="{{ $columna }}">{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                        <span class="field__hint">Solo se ofrecen datos que existen en la ficha del estudiante.</span>
                    </div>
                    <div class="field">
                        <label for="nueva_etiqueta">Etiqueta visible</label>
                        <input type="text" id="nueva_etiqueta" name="dpc_etiqueta" required maxlength="100"
                               value="{{ old('dpc_etiqueta') }}">
                        <span class="field__hint">Es el nombre que el estudiante verá en su perfil.</span>
                    </div>
                </div>

                <div class="form__row">
                    <div class="field">
                        <label for="nueva_vigencia">Vigencia</label>
                        <select id="nueva_vigencia" name="dpc_vigencia_meses">
                            <option value="">No vence</option>
                            @foreach ([3, 6, 12, 24] as $meses)
                                <option value="{{ $meses }}" @selected(old('dpc_vigencia_meses') == $meses)>
                                    {{ $meses }} meses
                                </option>
                            @endforeach
                        </select>
                        <span class="field__hint">Los datos permanentes (fecha de nacimiento y similares) no vencen.</span>
                    </div>
                    <div class="field">
                        <label for="nuevo_obligatorio">El estudiante debe llenarlo</label>
                        <select id="nuevo_obligatorio" name="dpc_obligatorio">
                            <option value="0">Opcional</option>
                            <option value="1" @selected(old('dpc_obligatorio') === '1')>Obligatorio</option>
                        </select>
                    </div>
                </div>

                <div class="form__row">
                    <div class="field">
                        <label for="nuevo_activo">Se pide en el perfil</label>
                        <select id="nuevo_activo" name="dpc_activo">
                            <option value="1" @selected(old('dpc_activo', '1') === '1')>Habilitado</option>
                            <option value="0" @selected(old('dpc_activo') === '0')>Deshabilitado (guardarlo para luego)</option>
                        </select>
                    </div>
                </div>

                <div class="actions">
                    <button type="submit" class="btn btn--primary">Agregar campo</button>
                    <a href="{{ route('admin.datos-precarga.index') }}" class="btn btn--ghost">Cancelar</a>
                </div>
            </form>
        @endif
    </div>
@endsection
