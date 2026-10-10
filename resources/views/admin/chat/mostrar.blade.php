@extends('layouts.plantilla_admin')

@section('title', 'Atender consulta #' . $hilo->hch_id)

@section('content')
    @include('partials.miga', [
        'miga' => [
            ['texto' => 'Atención'],
            ['texto' => 'Chats de soporte', 'ruta' => route('admin.chat.index')],
            ['texto' => 'Consulta #' . $hilo->hch_id],
        ],
    ])

    @include('partials.avisos')

    <div class="pagina-head">
        <div>
            <h1 class="pagina-head__titulo">
                Consulta #{{ $hilo->hch_id }}
                <span class="chip {{ $hilo->estado_chip }}">{{ $hilo->estado_etiqueta }}</span>
                <span class="chip chip--neutro" title="Nivel de atención del chat">Nivel: {{ $hilo->nivel_etiqueta }}</span>
            </h1>
            <p class="pagina-head__desc">
                {{ $hilo->usuario->usu_primer_nombre ?? 'Usuario' }}
                {{ $hilo->usuario->usu_primer_apellido ?? '' }}
                @if ($hilo->usuario)
                    · {{ $hilo->usuario->usu_correo_electronico }}
                @endif
                · abierta el {{ $hilo->created_at->format('d/m/Y H:i') }}
            </p>
        </div>

        <div class="pagina-head__acciones">
            <a href="{{ route('admin.chat.index') }}" class="btn btn--ghost">Volver a la bandeja</a>
        </div>
    </div>

    {{-- Escalado y cierre forzado --}}
    @if ($hilo->hch_estado !== 'cerrado' && $hilo->hch_estado !== 'pendiente_cierre')
        <div class="alert alert--warning">
            @if ($hilo->puedeEscalar())
                <form action="{{ route('admin.chat.escalar', $hilo->hch_id) }}" method="POST" style="margin: 0;">
                    @csrf
                    ¿La consulta excede tu nivel de atención?
                    <button type="submit" class="btn btn--sm" style="margin-left: 8px;">Escalar a nivel superior</button>
                    <span style="color: var(--gray-500);">Pasa de {{ strtolower($hilo->nivel_etiqueta) }} al siguiente rol y vuelve a la bandeja.</span>
                </form>
            @else
                Este chat ya está en el nivel más alto (administrador): no puede escalar más.
            @endif
        </div>
    @endif

    <div class="chat-panel">
        {{-- Conversación --}}
        <div class="card">
            @include('partials.chat.mensajes', ['hilo' => $hilo])

            @if ($hilo->hch_estado === 'activo')
                <form action="{{ route('admin.chat.enviar', $hilo->hch_id) }}" method="POST"
                      enctype="multipart/form-data" class="form" style="margin-top: 20px;">
                    @csrf

                    <div class="field">
                        <label for="mch_cuerpo">Respuesta</label>
                        <textarea name="mch_cuerpo" id="mch_cuerpo" rows="4"
                                  placeholder="Escribe la respuesta al estudiante…">{{ old('mch_cuerpo') }}</textarea>

                        @error('mch_cuerpo')
                            <span class="field__error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="imagen">Adjuntar evidencia</label>
                        <input type="file" name="imagen" id="imagen" accept="image/*">
                    </div>

                    <div class="actions">
                        <button type="submit" class="btn btn--primary">Enviar respuesta</button>
                    </div>
                </form>
            @endif
        </div>

        {{-- Acciones según el estado --}}
        <div>
            @if ($hilo->hch_estado === 'pendiente')
                <div class="card" style="border-left: 4px solid #d69a00;">
                    <h2 class="card__title" style="font-size: 1.05rem;">Sin reclamar</h2>
                    <p class="card__sub" style="margin-bottom: 16px;">
                        Otro administrador aún no tomó esta consulta. Reclámala para poder responder.
                    </p>

                    <form action="{{ route('admin.chat.reclamar', $hilo->hch_id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn--primary btn--block">Reclamar</button>
                    </form>
                </div>
            @elseif ($hilo->hch_estado === 'activo')
                <div class="card">
                    <h2 class="card__title" style="font-size: 1.05rem;">Proponer el cierre</h2>
                    <p class="card__sub" style="margin-bottom: 16px;">
                        Cuando la duda quede resuelta, propón el cierre. El estudiante tiene 7 días para
                        confirmar; si lo rechaza, el chat vuelve a estar activo contigo.
                    </p>

                    <form action="{{ route('admin.chat.proponer-cierre', $hilo->hch_id) }}" method="POST" class="form">
                        @csrf

                        <div class="field">
                            <label for="etiqueta_tema">Etiqueta del tema <span class="req">*</span></label>
                            <input type="text"
                                   name="etiqueta_tema"
                                   id="etiqueta_tema"
                                   required
                                   maxlength="100"
                                   placeholder="Ej: Problema de carga de archivos"
                                   value="{{ old('etiqueta_tema') }}">

                            @error('etiqueta_tema')
                                <span class="field__error">{{ $message }}</span>
                            @enderror

                            <span class="field__hint">Sirve para clasificar la consulta en el historial.</span>
                        </div>

                        <div class="actions">
                            <button type="submit" class="btn btn--primary btn--block">Solicitar cierre</button>
                        </div>
                    </form>
                </div>
            @elseif ($hilo->hch_estado === 'pendiente_cierre')
                <div class="card">
                    <h2 class="card__title" style="font-size: 1.05rem;">Esperando al estudiante</h2>
                    <p class="card__sub" style="margin-bottom: 0;">
                        Cierre propuesto el {{ $hilo->hch_fecha_solicitud_cierre?->format('d/m/Y') }} con la
                        etiqueta «{{ $hilo->hch_etiqueta_tema }}».
                        @if ($hilo->hch_fecha_solicitud_cierre)
                            Quedan {{ max(0, 7 - $hilo->hch_fecha_solicitud_cierre->diffInDays(now())) }} días
                            para que confirme.
                        @endif
                    </p>
                </div>
            @else
                <div class="card">
                    <h2 class="card__title" style="font-size: 1.05rem;">Consulta cerrada</h2>
                    <p class="card__sub" style="margin-bottom: 0;">
                        Cerrada el {{ $hilo->updated_at->format('d/m/Y') }}.
                        @if ($hilo->hch_etiqueta_tema)
                            Tema: {{ $hilo->hch_etiqueta_tema }}.
                        @endif
                    </p>
                </div>
            @endif

            {{-- Cierre forzado por conducta inadecuada: solo administrador.
                 Cierra sin esperar la confirmación del estudiante y deja el
                 motivo como mensaje en la conversación. --}}
            @if (Auth::user()->esAdministrador() && $hilo->hch_estado !== 'cerrado')
                <details class="card card--plana" style="margin-top: 16px;">
                    <summary class="card__title" style="cursor: pointer;">Cierre forzado (conducta inadecuada)</summary>
                    <p class="card__sub" style="margin: 12px 0 16px;">
                        Cierra la consulta sin esperar la confirmación del estudiante y deja el motivo como
                        constancia en la conversación. Úsalo solo ante conducta inadecuada o abuso del chat.
                    </p>

                    <form action="{{ route('admin.chat.cerrar-forzado', $hilo->hch_id) }}" method="POST" class="form"
                          onsubmit="return confirm('¿Cerrar de forma FORZADA esta consulta? El motivo quedará registrado.');">
                        @csrf

                        <div class="field">
                            <label for="motivo">Motivo <span class="req">*</span></label>
                            <textarea name="motivo" id="motivo" rows="3" required maxlength="500"
                                      placeholder="Describe la conducta que justifica el cierre…">{{ old('motivo') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn--danger btn--block">Cerrar de forma forzada</button>
                    </form>
                </details>
            @endif
        </div>
    </div>
@endsection
