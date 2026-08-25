@extends('layouts.plantilla_admin')

@section('title', 'Configurar Requisitos')

@section('content')
<div class="container main">
    {{-- Alertas de Laravel (Validaciones y éxito) --}}
    @if($errors->any())
        <div class="alert alert--error">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert--success">{{ session('success') }}</div>
    @endif

    <!-- FORMULARIO DE CREAR (C) -->
    @if(request('action') === 'crear')
      <div class="card">
        <h2 class="card__title">Nuevo Trámite Académico</h2>
        <p class="card__sub">Define qué documentos o condiciones necesita el estudiante para solicitar este trámite.</p>
        
        <form action="{{ route('admin.requisitos.store') }}" method="POST" class="form">
          @csrf {{-- Protección obligatoria de Laravel --}}
          
          <div class="field">
            <label for="nombre_tramite">Nombre del Trámite</label>
            <input type="text" name="nombre_tramite" id="nombre_tramite" placeholder="Ej: Certificación de Calificaciones" value="{{ old('nombre_tramite') }}" required />
          </div>

          <div class="field">
            <label for="documentos_necesarios">Requisitos / Documentación necesaria</label>
            <textarea name="documentos_necesarios" id="documentos_necesarios" placeholder="Lista aquí los requisitos..." required>{{ old('documentos_necesarios') }}</textarea>
          </div>

          <div class="actions">
            <button type="submit" class="btn btn--primary">Registrar Trámite</button>
            <a href="{{ route('admin.requisitos.index') }}" class="btn btn--dark">Cancelar</a>
          </div>
        </form>
      </div>

    <!-- LISTADO DE REQUISITOS (R) -->
    @else
      <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
          <div>
            <h2 class="card__title">Requisitos por Trámite</h2>
            <p class="card__sub" style="margin-bottom: 0;">Configura qué documentos se solicitan para cada tipo de trámite.</p>
          </div>
          <a href="{{ route('admin.requisitos.index', ['action' => 'crear']) }}" class="btn btn--primary">+ Agregar Trámite</a>
        </div>

        @if($requisitos->isEmpty())
          <p>Aún no hay requisitos configurados en el sistema. <a href="{{ route('admin.requisitos.index', ['action' => 'crear']) }}">¡Crea el primero aquí!</a></p>
        @else
          <div class="table-wrap">
            <table class="table">
              <thead>
                <tr>
                  <th>Trámite Académico</th>
                  <th>Requisitos Solicitados</th>
                  <th style="text-align: right;">Acciones</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($requisitos as $r)
                  <tr>
                    <td style="width: 30%;"><strong>{{ $r->req_nombre_requisito }}</strong></td>
                    <td style="white-space: pre-wrap; font-size: 0.9rem; color: var(--gray-700);">{{ $r->req_descripcion }}</td>
                    <td style="text-align: right; white-space: nowrap; display: flex; justify-content: flex-end; gap: 4px;">
                      
                      {{-- Formulario para eliminar protegido por DELETE --}}
                      <form action="{{ route('admin.requisitos.destroy', $r->req_id) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este trámite de la oferta estudiantil?');">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn--danger btn--sm">Eliminar</button>
                      </form>
                      
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    @endif
</div>
@endsection