@extends('layouts.plantilla_admin')

@section('title', 'Panel de Administración')

@section('content')
<div class="container main">
    <section class="hero" style="background: linear-gradient(135deg, #111 0%, #222 100%); border-left: 6px solid var(--red);">
      <h1>Panel de Control Administrativo</h1>
      <p>Revisa la documentación adjunta, aprueba o rechaza los trámites académicos en tiempo real.</p>
    </section>

    {{-- Manejo de mensajes de éxito enviados desde el Controlador --}}
    @if(session('success'))
        <div class="alert alert--success">{{ session('success') }}</div>
    @endif

    <div class="stats">
      <div class="stat"><div class="stat__label">Total Solicitudes</div><div class="stat__value">{{ $stats['total'] }}</div></div>
      <div class="stat" style="border-left-color: #ffd6d6;"><div class="stat__label">Pendientes</div><div class="stat__value">{{ $stats['pendiente'] }}</div></div>
      <div class="stat" style="border-left-color: #d6f5e3;"><div class="stat__label">Aprobadas</div><div class="stat__value">{{ $stats['aprobada'] }}</div></div>
      <div class="stat" style="border-left-color: var(--red);"><div class="stat__label">Rechazadas</div><div class="stat__value">{{ $stats['rechazada'] }}</div></div>
    </div>

    <div class="card">
      <h2 class="card__title">Listado de Solicitudes Estudiantiles</h2>
      <p class="card__sub">Administra las peticiones ingresadas al sistema por los estudiantes.</p>

      @if($solicitudes->isEmpty())
        <p>No hay solicitudes registradas en el sistema todavía.</p>
      @else
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>Estudiante</th>
                <th>Cédula</th>
                <th>Tipo de Trámite</th>
                <th>Detalles/Asunto</th>
                <th>Estado</th>
                <th style="text-align: right;">Acciones</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($solicitudes as $s)
                <tr>
                  <td>
                    <strong>{{ $s->usuario->usu_primer_nombre }} {{ $s->usuario->usu_primer_apellido }}</strong>
                  </td>
                  <td>
                    <span style="font-size: 0.85rem; display: block; color: var(--gray-700);">C.I: {{ $s->usuario->usu_numero_documento }}</span>
                  </td>
                  <td><span style="font-weight: 600; color: var(--red);">{{ $s->tipoSolicitud->tsi_nombre_tipo }}</span></td>
                  <td style="max-width: 250px; font-size: 0.9rem;">{{ $s->sol_motivo_detallado ?? 'Sin motivo detallado' }}</td>
                  <td>
                    <span class="badge badge--{{ strtolower($s->estadoActual->eso_nombre_estado) }}">
                        {{ strtoupper($s->estadoActual->eso_nombre_estado) }}
                    </span>
                  </td>
                  <td style="text-align: right; white-space: nowrap;">
                    @if(strtolower($s->estadoActual->eso_nombre_estado) === 'pendiente')
                      {{-- Enlaces a las rutas definidas en web.php --}}
                      <a href="{{ route('admin.dashboard.estado', ['id' => $s->sol_id, 'accion' => 'aprobar']) }}" class="btn btn--sm" style="background: #22a35a; color: white; margin-right: 4px;">Aprobar</a>
                      <a href="{{ route('admin.dashboard.estado', ['id' => $s->sol_id, 'accion' => 'rechazar']) }}" class="btn btn--danger btn--sm" onclick="return confirm('¿Seguro que deseas rechazar esta solicitud?')">Rechazar</a>
                    @else
                      <span style="color: var(--gray-400); font-size: 0.85rem; font-style: italic;">Sin acciones</span>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
</div>
@endsection