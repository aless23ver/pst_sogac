@extends('layouts.plantila_admin')

@section('title', 'Gestión de Requisitos')

@section('content')
  <main class="main container">

    <section class="hero" style="background: linear-gradient(135deg, #111 0%, #222 100%); border-left: 6px solid var(--red);">
      <h1>Panel de Control Administrativo</h1>
      <p>Revisa la documentación adjunta, aprueba o rechaza los trámites académicos en tiempo real.</p>
    </section>

    {{-- Mostrar mensajes de éxito pasados desde el controlador --}}
    @if(session('success'))
      <div class="alert alert--success">{{ session('success') }}</div>
    @endif

    <div class="stats">
      <div class="stat"><div class="stat__label">Total Solicitudes</div><div class="stat__value">{{ $total }}</div></div>
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
                <th>Cédula / Carrera</th>
                <th>Tipo de Trámite</th>
                <th>Detalles/Asunto</th>
                <th>Estado</th>
                <th style="text-align: right;">Acciones</th>
              </tr>
            </thead>
            <tbody>
              @foreach($solicitudes as $s)
                <tr>
                  <td>
                    {{-- Accedemos a los datos del usuario a través de la relación definida en el modelo --}}
                    <strong>{{ $s->usuario->nombre }} {{ $s->usuario->apellido }}</strong>
                  </td>
                  <td>
                    <span style="font-size: 0.85rem; display: block; color: var(--gray-700);">C.I: {{ $s->usuario->cedula }}</span>
                    <span style="font-size: 0.85rem; display: block; color: var(--gray-400);">{{ $s->usuario->carrera ?: 'No especificada' }}</span>
                  </td>
                  <td><span style="font-weight: 600; color: var(--red);">{{ $s->tipo }}</span></td>
                  <td style="max-width: 250px; font-size: 0.9rem;">{{ $s->asunto }}</td>
                  <td><span class="badge badge--{{ $s->estado }}">{{ $s->estado }}</span></td>
                  <td style="text-align: right; white-space: nowrap;">
                    @if($s->estado === 'pendiente')
                      <a href="{{ route('admin.solicitudes.estado', [$s->id, 'aprobar']) }}" class="btn btn--sm" style="background: #22a35a; color: white; margin-right: 4px;">Aprobar</a>
                      <a href="{{ route('admin.solicitudes.estado', [$s->id, 'rechazar']) }}" class="btn btn--danger btn--sm" onclick="return confirm('¿Seguro que deseas rechazar esta solicitud?')">Rechazar</a>
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

  </main>
  <footer class="footer">
    <div class="container">
      <p>&copy; {{ date('Y') }} Solicítalo — Panel Administrativo</p>
    </div>
  </footer>
</body>
</html>
@endsection