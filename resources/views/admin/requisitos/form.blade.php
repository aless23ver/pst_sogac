@extends('layouts.admin')

@section('title', $requisito->exists ? 'Editar Trámite' : 'Nuevo Trámite')

@section('content')
    {{-- Mostrar errores de validación --}}
    @if ($errors->any())
        <div class="alert alert--error">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <h2 class="card__title">{{ $requisito->exists ? 'Editar Trámite y Requisitos' : 'Nuevo Trámite Académico' }}</h2>
        <p class="card__sub">Define qué documentos o condiciones necesita el estudiante para solicitar este trámite.</p>
        
        {{-- El formulario apunta a update si existe el id, o a store si es nuevo --}}
        <form action="{{ $requisito->exists ? route('admin.requisitos.update', $requisito) : route('admin.requisitos.store') }}" method="POST" class="form">
            @csrf
            {{-- Si estamos editando, inyectamos el método PUT --}}
            @if($requisito->exists)
                @method('PUT')
            @endif

            <div class="field">
                <label for="nombre_tramite">Nombre del Trámite</label>
                {{-- old('campo', valor_por_defecto) recuerda lo que el usuario escribió si hay un error de validación --}}
                <input type="text" name="nombre_tramite" id="nombre_tramite" placeholder="Ej: Certificación de Calificaciones..." value="{{ old('nombre_tramite', $requisito->nombre_tramite) }}" required />
            </div>

            <div class="field">
                <label for="documentos_necesarios">Requisitos / Documentación necesaria</label>
                <textarea name="documentos_necesarios" id="documentos_necesarios" placeholder="Lista aquí los requisitos..." required>{{ old('documentos_necesarios', $requisito->documentos_necesarios) }}</textarea>
            </div>

            <div class="actions">
                <button type="submit" class="btn btn--primary">{{ $requisito->exists ? 'Guardar Cambios' : 'Registrar Trámite' }}</button>
                <a href="{{ route('admin.requisitos.index') }}" class="btn btn--dark">Cancelar</a>
            </div>
        </form>
    </div>
@endsection