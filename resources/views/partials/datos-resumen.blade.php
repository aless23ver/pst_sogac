{{--
    Filas <dl> de los datos de precarga que el estudiante ya cargó.

    Uso:
        @include('partials.datos-resumen', [
            'datos' => $usuario->datosPrecarga(),   // campo => valor
            'campos' => DatosPrecargaConfig::camposActivos(),
        ])

    Los campos vacíos se omiten: el panel es "lo que se adjuntará", no un
    formulario de revisión.
--}}
@foreach ($campos as $campo)
    @php $valor = $datos[$campo->dpc_campo] ?? null; @endphp
    @if ($valor !== null && $valor !== '')
        <div class="datos__fila">
            <dt>{{ $campo->dpc_etiqueta }}</dt>
            <dd>{{ $valor }}</dd>
        </div>
    @endif
@endforeach
