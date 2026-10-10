@extends('layouts.plantilla_user')

@section('title', $tramite->tsi_nombre_tipo)

@section('content')
<nav class="miga" aria-label="Ruta de navegación">
  <a href="{{ route('user.tramites.index') }}" class="miga__actual">Trámites</a>
  <span class="miga__sep" aria-hidden="true">/</span>
  <span class="miga__actual" aria-current="page">{{ $tramite->tsi_nombre_tipo }}</span>
</nav>

<div class="pagina-head">
  <div>
    <h1 class="pagina-head__titulo">{{ $tramite->tsi_nombre_tipo }}</h1>
    @if ($tramite->tsi_descripcion)
      <p class="pagina-head__desc">{{ $tramite->tsi_descripcion }}</p>
    @endif
  </div>
</div>

@if ($errors->any())
  <div class="alert alert--error">
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

{{-- Requisitos que el administrador le asigno a ESTE tramite --}}
<div class="card">
  <h2 class="card__title">Documentos que debes tener listos</h2>

  @if ($tramite->requisitos->isEmpty())
    <p class="card__sub" style="margin-top: 10px;">Este trámite no pide requisitos adicionales.</p>
  @else
    <ul class="lista-simple" style="margin-top: 14px;">
      @foreach ($tramite->requisitos as $req)
        <li class="lista-simple__detalle">
          {{ $req->req_nombre_requisito }}
          @if ($req->pivot->tsr_es_obligatorio)
            <span class="badge badge--rechazada">Obligatorio</span>
          @else
            <span class="badge badge--activo">Opcional</span>
          @endif
        </li>
      @endforeach
    </ul>
  @endif
</div>

{{-- Datos de precarga: se adjuntan solos a la solicitud. El aviso empuja a
     completarlos ANTES de enviar, para que el trámite no llegue con huecos
     ni con datos que ya vencieron según la vigencia del administrador. --}}
@if ($faltanObligatorios->isNotEmpty() || $precargaVencida)
  <div class="alert alert--warning" style="margin-top: 20px;">
    <strong>Antes de enviar:</strong>
    @if ($faltanObligatorios->isNotEmpty())
      te falta cargar {{ $faltanObligatorios->count() === 1 ? 'un dato' : $faltanObligatorios->count().' datos' }} obligatorio(s):
      {{ $faltanObligatorios->pluck('dpc_etiqueta')->implode(', ') }}.
    @endif
    @if ($faltanObligatorios->isNotEmpty() && $precargaVencida)
      Además, tus
    @elseif ($precargaVencida)
      Tus
    @endif
    @if ($precargaVencida)
      datos de precarga perdieron vigencia y conviene refrescarlos.
    @endif
    <br>
    Complétalos en <a href="{{ route('user.datos-perfil') }}">Mis datos</a> y vuelve a esta pantalla:
    el formulario los recordará.
  </div>
@endif

<div class="card" style="margin-top: 20px;">
  <h2 class="card__title">Tus datos que se adjuntarán</h2>

  @if (collect($datosPrecarga)->filter(fn ($valor) => $valor !== null && $valor !== '')->isEmpty())
    <p class="card__sub" style="margin-top: 10px;">
      Todavía no has cargado tus datos de precarga.
      <a href="{{ route('user.datos-perfil') }}">Cárgalos aquí</a> y se adjuntarán automáticamente a tus solicitudes.
    </p>
  @else
    <p class="card__sub" style="margin-top: 10px;">
      Viene de <a href="{{ route('user.datos-perfil') }}">Mis datos</a>: si algo cambió, actualízalo allí.
    </p>
    <dl class="datos" style="margin-top: 14px;">
      @include('partials.datos-resumen', ['datos' => $datosPrecarga, 'campos' => $camposPrecarga])
    </dl>
  @endif
</div>

<form action="{{ route('user.tramites.store', $tramite->tsi_id) }}" method="POST" class="card" style="margin-top: 20px;">
  @csrf

  <h2 class="card__title">Cuéntanos qué necesitas</h2>

  <div class="field" style="margin-top: 16px;">
    <label for="motivo">Motivo de la solicitud</label>
    <textarea name="motivo" id="motivo" rows="5" placeholder="Describe tu solicitud con el mayor detalle posible..." required>{{ old('motivo') }}</textarea>
    <span class="field__hint">Mientras más claro sea el motivo, más rápido podrás resolver el trámite.</span>
  </div>

  <div class="actions">
    <button type="submit" class="btn btn--primary">Enviar solicitud</button>
    <a href="{{ route('user.tramites.index') }}" class="btn btn--ghost">Cancelar</a>
  </div>
</form>
@endsection