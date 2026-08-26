@extends('layouts.plantilla_soporte')

@section('title', 'Centro de Soporte')

@section('content')
<div class="container main" style="padding: 2rem 0;">

    {{-- ========================================================= --}}
    {{-- VISTA DEL ADMINISTRADOR --}}
    {{-- ========================================================= --}}
    @if(auth()->user()->usu_rol === 'admin')
        
        <h2 style="font-size: 2rem; color: var(--black); margin-bottom: 8px;">Panel de Soporte Técnico</h2>
        <p style="color: var(--gray-700); margin-bottom: 30px;">Bandeja de entrada y gestión de tickets de estudiantes.</p>

        <!-- Sección 1: Bandeja de Entrada (Pendientes) -->
        <h3 style="color: var(--red); border-bottom: 2px solid var(--red); padding-bottom: 10px; margin-bottom: 20px;">
            Tickets Nuevos (Pendientes)
        </h3>
        
        @if($hilosPendientes->count() > 0)
            <div style="background: white; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; margin-bottom: 40px;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead style="background: #f8f9fa;">
                        <tr>
                            <th style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">ID</th>
                            <th style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">Estudiante</th>
                            <th style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">Fecha</th>
                            <th style="padding: 12px 15px; border-bottom: 1px solid #dee2e6; text-align: center;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($hilosPendientes as $pendiente)
                        <tr>
                            <td style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">#{{ $pendiente->hch_id }}</td>
                            <td style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">
                                {{ $pendiente->usuario->usu_primer_nombre ?? 'Usuario' }} {{ $pendiente->usuario->usu_primer_apellido ?? '' }}
                            </td>
                            <td style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">{{ $pendiente->created_at->diffForHumans() }}</td>
                            <td style="padding: 12px 15px; border-bottom: 1px solid #dee2e6; text-align: center;">
                                <form action="{{ route('chat.reclamar', $pendiente->hch_id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn--primary btn--sm" style="background: #28a745; border: none; cursor: pointer;">Reclamar y Atender</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert" style="background: #e3f2fd; color: #004085; padding: 15px; border-radius: 8px; margin-bottom: 40px;">
                ¡Excelente trabajo! No hay tickets pendientes en este momento.
            </div>
        @endif
        <!-- Sección 2: Mis Chats en Curso (Para el Admin) -->
        @if($hilosActivosAdmin->count() > 0)
            <h3 style="color: #17a2b8; border-bottom: 2px solid #17a2b8; padding-bottom: 10px; margin-bottom: 20px; margin-top: 40px;">
                Mis Chats en Curso
            </h3>
            <div style="background: white; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; margin-bottom: 40px;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead style="background: #f8f9fa;">
                        <tr>
                            <th style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">ID</th>
                            <th style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">Estudiante</th>
                            <th style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">Estado</th>
                            <th style="padding: 12px 15px; border-bottom: 1px solid #dee2e6; text-align: center;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($hilosActivosAdmin as $activo)
                        <tr>
                            <td style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">#{{ $activo->hch_id }}</td>
                            <td style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">
                                {{ $activo->usuario->usu_primer_nombre ?? 'Usuario' }} {{ $activo->usuario->usu_primer_apellido ?? '' }}
                            </td>
                            <td style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">
                                <strong>{{ ucfirst(str_replace('_', ' ', $activo->hch_estado)) }}</strong>
                            </td>
                            <td style="padding: 12px 15px; border-bottom: 1px solid #dee2e6; text-align: center;">
                                <a href="{{ route('chat.mostrar', $activo->hch_id) }}" class="btn btn--primary btn--sm" style="background: #17a2b8; border: none; text-decoration: none; color: white; padding: 6px 12px; border-radius: 4px;">Continuar Chat</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif


    {{-- ========================================================= --}}
    {{-- VISTA DEL ESTUDIANTE (USUARIO) --}}
    {{-- ========================================================= --}}
    @else
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
            <div>
                <h2 style="font-size: 2rem; color: var(--black); margin-bottom: 8px;">Mis Consultas</h2>
                <p style="color: var(--gray-700);">Ponte en contacto con el soporte técnico.</p>
            </div>
            
            <!-- Botón Inteligente: Crear o ir al activo -->
            <div>
                @if($hiloActivo)
                    <a href="{{ route('chat.mostrar', $hiloActivo->hch_id) }}" class="btn btn--primary" style="background: #17a2b8; text-decoration: none; display: inline-block;">
                        Ir a mi consulta actual
                    </a>
                @else
                    <form action="{{ route('chat.iniciar') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn--primary" style="background: var(--red); border: none; cursor: pointer;">
                            + Crear Nuevo Ticket
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if($hiloActivo)
            <div class="alert" style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; border: 1px solid #ffeeba; margin-bottom: 40px;">
                <strong>Aviso:</strong> Tienes un ticket en curso (Estado: {{ ucfirst($hiloActivo->hch_estado) }}). Resuélvelo antes de abrir uno nuevo.
            </div>
        @endif

    @endif


    {{-- ========================================================= --}}
    {{-- HISTORIAL DE TICKETS CERRADOS (COMPARTIDO) --}}
    {{-- ========================================================= --}}
    
    <h3 style="color: var(--gray-700); border-bottom: 2px solid var(--gray-400); padding-bottom: 10px; margin-bottom: 20px;">
        Historial de Tickets Resueltos
    </h3>

    @if($hilos->count() > 0)
        <div style="background: white; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead style="background: #f8f9fa;">
                    <tr>
                        <th style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">ID</th>
                        <!-- Condicional para la cabecera de la etiqueta -->
                        @if(auth()->user()->usu_rol === 'admin')
                            <th style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">Tema / Etiqueta</th>
                            <th style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">Estudiante</th>
                        @endif
                        <th style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">Atendido por</th>
                        <th style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">Fecha Cierre</th>
                        <th style="padding: 12px 15px; border-bottom: 1px solid #dee2e6; text-align: center;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($hilos as $cerrado)
                    <tr>
                        <td style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">#{{ $cerrado->hch_id }}</td>
                        
                        <!-- Condicional para el contenido de la etiqueta -->
                        @if(auth()->user()->usu_rol === 'admin')
                            <td style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">
                                <span style="background: #e2e3e5; padding: 3px 8px; border-radius: 12px; font-size: 0.85rem;">
                                    {{ $cerrado->hch_etiqueta_tema ?? 'Sin etiqueta' }}
                                </span>
                            </td>
                            <td style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">
                                {{ $cerrado->usuario->usu_primer_nombre }}
                            </td>
                        @endif
                        
                        <td style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">
                            {{ $cerrado->admin->usu_primer_nombre ?? 'Sistema' }}
                        </td>
                        <td style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">
                            {{ $cerrado->updated_at->format('d/m/Y') }}
                        </td>
                        <td style="padding: 12px 15px; border-bottom: 1px solid #dee2e6; text-align: center;">
                            <a href="{{ route('chat.mostrar', $cerrado->hch_id) }}" style="color: #007bff; text-decoration: none; font-weight: bold;">Ver Chat</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Paginación nativa de Laravel -->
        <div style="margin-top: 20px;">
            {{ $hilos->links() }}
        </div>
    @else
        <p style="color: var(--gray-700);">No hay tickets resueltos en el historial.</p>
    @endif

</div>
@endsection