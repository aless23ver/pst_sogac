@extends('layouts.plantilla_user')

@section('title', 'Consulta #' . $hilo->hch_id)

@section('content')
    @include('partials.miga', [
        'miga' => [
            ['texto' => 'Ayuda'],
            ['texto' => 'Chat de soporte', 'ruta' => route('user.ayuda.chat.index')],
            ['texto' => 'Consulta #' . $hilo->hch_id],
        ],
    ])

    @include('partials.avisos')

    <div class="pagina-head">
        <div>
            <h1 class="pagina-head__titulo">
                Consulta #{{ $hilo->hch_id }}
                <span class="chip {{ $hilo->estado_chip }}">{{ $hilo->estado_etiqueta }}</span>
            </h1>
            <p class="pagina-head__desc">
                Abierta el {{ $hilo->created_at->format('d/m/Y') }}.
                @if ($hilo->admin)
                    La atiende {{ $hilo->admin->usu_primer_nombre }} {{ $hilo->admin->usu_primer_apellido }}.
                @else
                    Espera atención de nivel <strong>{{ $hilo->nivel_etiqueta }}</strong>.
                @endif
            </p>
        </div>

        <div class="pagina-head__acciones">
            <a href="{{ route('user.ayuda.chat.index') }}" class="btn btn--ghost">Volver al chat</a>
        </div>
    </div>

    {{-- Escalado: si la atención actual no basta, el estudiante puede pedir
         que la consulta suba al siguiente nivel (analista o administrador). --}}
    @if ($hilo->puedeEscalar() && in_array($hilo->hch_estado, ['pendiente', 'activo']))
        <div class="alert alert--info">
            <form action="{{ route('user.ayuda.chat.escalar', $hilo->hch_id) }}" method="POST" style="margin: 0;">
                @csrf
                ¿Necesitas hablar con alguien más arriba?
                <button type="submit" class="btn btn--sm" style="margin-left: 8px;">Solicitar nivel superior</button>
                <span style="color: var(--gray-500);">Actualmente en nivel {{ strtolower($hilo->nivel_etiqueta) }}.</span>
            </form>
        </div>
    @endif

    <div class="card">
        @include('partials.chat.mensajes', ['hilo' => $hilo])
    </div>

    {{-- El bloque de acción depende del estado: confirmar, cerrado o responder. --}}
    <div style="margin-top: 22px;">
        @if ($hilo->hch_estado === 'pendiente_cierre')
            <div class="card" style="border-left: 4px solid #d69a00;">
                <h2 class="card__title">¿Se resolvió tu duda?</h2>
                <p class="card__sub" style="margin-bottom: 18px;">
                    {{ $hilo->hch_etiqueta_tema ? 'Tema: ' . $hilo->hch_etiqueta_tema . '.' : '' }}
                    Si confirmas, la consulta se archiva. Si todavía no, vuelve al chat con el mismo
                    administrador y seguid hablando.
                </p>

                <div class="actions">
                    <form action="{{ route('user.ayuda.chat.confirmar', $hilo->hch_id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn--primary">Sí, se resolvió</button>
                    </form>

                    <form action="{{ route('user.ayuda.chat.rechazar', $hilo->hch_id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn--ghost">No, aún necesito ayuda</button>
                    </form>
                </div>
            </div>
        @elseif ($hilo->hch_estado === 'cerrado')
            <div class="card">
                <p class="estado-vacio" style="margin: 0;">
                    Esta consulta se cerró el {{ $hilo->updated_at->format('d/m/Y') }}.
                    <br>
                    Si sigue haciendo falta, <a href="{{ route('user.ayuda.chat.index') }}">abre una nueva consulta</a>.
                </p>
            </div>
        @else
            <div class="card">
                <form action="{{ route('user.ayuda.chat.enviar', $hilo->hch_id) }}" method="POST"
                      enctype="multipart/form-data" class="form">
                    @csrf

                    <div class="field">
                        <label for="mch_cuerpo">Mensaje</label>
                        <textarea name="mch_cuerpo" id="mch_cuerpo" rows="4"
                                  placeholder="Escribe tu respuesta aquí…">{{ old('mch_cuerpo') }}</textarea>

                        @error('mch_cuerpo')
                            <span class="field__error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="imagen">Adjuntar evidencia</label>
                        <input type="file" name="imagen" id="imagen" accept="image/*">
                        <span class="field__hint">Opcional. JPG o PNG, hasta 2 MB.</span>
                    </div>

                    <div class="actions">
                        <button type="submit" class="btn btn--primary">Enviar mensaje</button>
                    </div>
                </form>
            </div>
        @endif
    </div>
@endsection
