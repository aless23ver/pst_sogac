@extends('layouts.plantilla_admin')

@section('title', 'Datos de estudiantes')

@section('content')
    @php
        // El estado de vigencia se calcula con la vigencia más laxa entre los
        // campos que vencen: si la más larga ya venció, todas vencieron.
        $estadoVigencia = function ($estudiante) use ($mesesVigencia) {
            if ((int) $estudiante->datos_cargados === 0) {
                return ['Sin datos', 'badge--rechazada'];
            }

            $vencido = $mesesVigencia !== null
                && ($estudiante->usu_datos_ultima_actualizacion === null
                    || $estudiante->usu_datos_ultima_actualizacion->lt(now()->subMonths($mesesVigencia)));

            return $vencido
                ? ['Desactualizado', 'badge--pendiente']
                : ['Vigente', 'badge--activo'];
        };

        $hayFiltros = $filtros['q'] !== '' || $filtros['datos'] !== '' || $filtros['vigencia'] !== '';
    @endphp

    @include('partials.miga', ['miga' => [['texto' => 'Administración'], ['texto' => 'Datos de precarga'], ['texto' => 'Datos de estudiantes']]])

    @include('partials.avisos')

    <div class="pagina-head">
        <div>
            <h1 class="pagina-head__titulo">Datos de estudiantes</h1>
            <p class="pagina-head__desc">
                Qué datos de precarga tiene cargado cada estudiante, cuántos le faltan de los
                habilitados y si siguen vigentes. Así sabes a quién citar antes de que un trámite
                llegue con datos vacíos o desactualizados.
            </p>
        </div>

        <div class="pagina-head__acciones">
            <a href="{{ route('admin.datos-precarga.index') }}" class="btn btn--ghost">Configurar campos</a>
        </div>
    </div>

    <div class="mini-stats" style="margin-bottom: 24px;">
        <div class="mini-stat">
            <div class="mini-stat__label">Estudiantes</div>
            <div class="mini-stat__value">{{ $resumen['total'] }}</div>
        </div>
        <div class="mini-stat">
            <div class="mini-stat__label">Datos completos</div>
            <div class="mini-stat__value" style="color: #14683a;">{{ $resumen['completos'] }}</div>
        </div>
        <div class="mini-stat">
            <div class="mini-stat__label">Sin datos</div>
            <div class="mini-stat__value" style="color: var(--red-dark);">{{ $resumen['sinDatos'] }}</div>
        </div>
        <div class="mini-stat">
            <div class="mini-stat__label">Desactualizados</div>
            <div class="mini-stat__value" style="color: #8a5a00;">{{ $resumen['desactualizados'] }}</div>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('admin.datos-estudiantes.index') }}" class="toolbar">
            <div class="filtros">
                <input type="text"
                       name="q"
                       value="{{ $filtros['q'] }}"
                       placeholder="Buscar por nombre, apellido, correo o documento…"
                       aria-label="Buscar estudiante">

                <select name="datos" aria-label="Filtrar por completitud de datos">
                    <option value="">Cualquier completitud</option>
                    <option value="completos" @selected($filtros['datos'] === 'completos')>Con todos los datos</option>
                    <option value="parciales" @selected($filtros['datos'] === 'parciales')>Con datos a medias</option>
                    <option value="sin-datos" @selected($filtros['datos'] === 'sin-datos')>Sin ningún dato</option>
                </select>

                <select name="vigencia" aria-label="Filtrar por vigencia">
                    <option value="">Vigentes y desactualizados</option>
                    <option value="vigentes" @selected($filtros['vigencia'] === 'vigentes')>Solo vigentes</option>
                    <option value="desactualizados" @selected($filtros['vigencia'] === 'desactualizados')>Solo desactualizados</option>
                </select>
            </div>

            <div class="toolbar__grupo">
                <button type="submit" class="btn btn--primary btn--sm">Filtrar</button>

                @if ($hayFiltros)
                    <a href="{{ route('admin.datos-estudiantes.index') }}" class="btn btn--ghost btn--sm">Limpiar</a>
                @endif
            </div>
        </form>

        @if ($campos->isEmpty())
            <p class="estado-vacio">
                No hay campos de precarga habilitados.
                <br>
                <a href="{{ route('admin.datos-precarga.index') }}">Configura los campos</a> para empezar a pedirlos.
            </p>
        @elseif ($estudiantes->isEmpty())
            @if ($hayFiltros)
                <p class="estado-vacio">
                    Ningún estudiante coincide con el filtro.
                    <br>
                    <a href="{{ route('admin.datos-estudiantes.index') }}">Quitar los filtros</a> para ver el padrón completo.
                </p>
            @else
                <p class="estado-vacio">Todavía no hay estudiantes registrados.</p>
            @endif
        @else
            <p class="lista__conteo">
                {{ $estudiantes->total() }}
                {{ $estudiantes->total() === 1 ? 'estudiante' : 'estudiantes' }} ·
                {{ $estudiantes->firstItem() }}–{{ $estudiantes->lastItem() }} de {{ $estudiantes->total() }}.
            </p>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Estudiante</th>
                            <th>Datos cargados</th>
                            <th>Última actualización</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($estudiantes as $estudiante)
                            @php [$etiqueta, $clase] = $estadoVigencia($estudiante); @endphp
                            <tr>
                                <td>
                                    <strong>{{ $estudiante->nombre_completo }}</strong>
                                    <br>
                                    <small style="color: var(--gray-400);">
                                        {{ $estudiante->usu_numero_documento }} · {{ $estudiante->usu_correo_electronico }}
                                    </small>
                                </td>

                                <td>
                                    @foreach ($campos as $campo)
                                        @php $valor = $estudiante->{$campo->dpc_campo}; @endphp
                                        <span class="badge {{ $valor !== null && $valor !== '' ? 'badge--activo' : 'badge--rechazada' }}"
                                              title="{{ $campo->dpc_etiqueta }}: {{ $valor !== null && $valor !== '' ? $valor : 'sin cargar' }}">
                                            {{ $campo->dpc_etiqueta }}
                                        </span>
                                    @endforeach
                                    <br>
                                    <small style="color: var(--gray-400);">
                                        {{ $estudiante->datos_cargados }} de {{ $campos->count() }} habilitados
                                    </small>
                                </td>

                                <td>
                                    @if ($estudiante->usu_datos_ultima_actualizacion)
                                        {{ $estudiante->usu_datos_ultima_actualizacion->format('d/m/Y H:i') }}
                                    @else
                                        <span style="color: var(--gray-400);">Nunca</span>
                                    @endif
                                </td>

                                <td>
                                    <span class="badge {{ $clase }}">{{ $etiqueta }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $estudiantes->links('vendor.pagination.custom', ['etiqueta' => 'estudiantes']) }}
        @endif
    </div>
@endsection
