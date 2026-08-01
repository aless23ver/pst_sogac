@extends('layouts.admin')

@section('title', 'Configurar Requisitos')

@section('content')
    @if(session('success'))
        <div class="alert alert--success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h2 class="card__title">Requisitos por Trámite</h2>
                <p class="card__sub" style="margin-bottom: 0;">Configura qué documentos se solicitan para cada tipo de trámite.</p>
            </div>
            <a href="{{ route('admin.requisitos.create') }}" class="btn btn--primary">+ Agregar Trámite</a>
        </div>

        @if($requisitos->isEmpty())
            <p>Aún no hay requisitos configurados en el sistema. <a href="{{ route('admin.requisitos.create') }}">¡Crea el primero aquí!</a></p>
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
                                <td style="width: 30%;"><strong>{{ $r->nombre_tramite }}</strong></td>
                                <td style="white-space: pre-wrap; font-size: 0.9rem; color: var(--gray-700);">{{ $r->documentos_necesarios }}</td>
                                <td style="text-align: right; white-space: nowrap; display: flex; justify-content: flex-end; gap: 4px;">
                                    {{-- Botón Editar --}}
                                    <a href="{{ route('admin.requisitos.edit', $r) }}" class="btn btn--dark btn--sm">Editar</a>
                                    
                                    {{-- Botón Eliminar (Debe ser un formulario por seguridad en Laravel) --}}
                                    <form action="{{ route('admin.requisitos.destroy', $r) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este trámite?')">
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
@endsection