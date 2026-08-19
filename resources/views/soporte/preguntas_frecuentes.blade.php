@extends('layouts.plantilla_soporte')

@section('title', 'Preguntas Frecuentes')

@section('content')
<div class="container main">
    
    <!-- Barra de Herramientas -->
    <div style="display: flex; justify-content: flex-end; gap: 12px; margin-bottom: 20px;">
        <button onclick="document.getElementById('modalCrear').style.display='block'" class="btn btn--primary btn--sm" style="background: var(--red);">
            Añadir
        </button>
        <button id="btnModoEditar" onclick="activarModo('editar')" class="btn btn--dark btn--sm">
            Editar
        </button>
        <button id="btnModoEliminar" onclick="activarModo('eliminar')" class="btn btn--ghost btn--sm" style="color: var(--black); border-color: var(--gray-400);">
            Eliminar
        </button>
    </div>

    <!-- Mensaje dinámico -->
    <div id="barraEstado" class="alert alert--pendiente" style="display: none; text-align: center; font-weight: bold; transition: all 0.3s ease;">
        Selecciona una pregunta de la lista para modificarla...
    </div>

    @if(session('message'))
        <div class="alert alert--success">{{ session('message') }}</div>
    @endif

    <!-- Título -->
    <div style="margin-bottom: 32px;">
        <h2 style="font-size: 2rem; color: var(--black); margin-bottom: 8px;">Preguntas Frecuentes</h2>
        <p style="color: var(--gray-700);">Gestiona las dudas comunes de los usuarios.</p>
    </div>

    <!-- Lista de Acordeones -->
    <div style="display: flex; flex-direction: column; gap: 12px;">
        @foreach($preguntas as $pregunta)
            
            <details class="acordeon-item" style="background: var(--white); border: 1px solid var(--gray-200); border-radius: var(--radius-sm); overflow: hidden;">
                <summary onclick="interactuarElemento(event, {{ $pregunta->id }})" style="padding: 16px 20px; font-weight: 600; cursor: pointer; list-style: none; display: flex; justify-content: space-between; align-items: center; background: var(--white);">
                    {{ $pregunta->pregunta }}
                    
                    <!-- Icono de Flecha SVG -->
                    <svg class="icono-flecha" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--gray-700)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </summary>
                
                <!-- Contenedor animado para el texto -->
                <div class="acordeon-contenido" style="padding: 0 20px 20px 20px; color: var(--gray-700); border-top: 1px solid var(--gray-100); margin-top: 5px; padding-top: 15px;">
                    {{ $pregunta->respuesta }}
                </div>
            </details>

            <!-- Formulario de Eliminación oculto -->
            <form id="form-eliminar-{{ $pregunta->id }}" method="POST" action="{{ route('soporte.destroy', $pregunta->id) }}" style="display:none;">
                @csrf @method('DELETE')
            </form>

            <!-- Modal de Edición animado -->
            <div id="modalEditar{{ $pregunta->id }}" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000;">
                <div class="card modal-content" style="position: relative; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 500px; box-shadow: var(--shadow-lg);">
                    <h3 class="card__title">Editar Pregunta</h3>
                    <form method="POST" action="{{ route('soporte.update', $pregunta->id) }}" class="form" style="margin-top: 20px;">
                        @csrf @method('PUT')
                        <div class="field">
                            <label>Pregunta</label>
                            <input type="text" name="pregunta" value="{{ $pregunta->pregunta }}" required>
                        </div>
                        <div class="field">
                            <label>Respuesta</label>
                            <textarea name="respuesta" required>{{ $pregunta->respuesta }}</textarea>
                        </div>
                        <div class="actions" style="justify-content: flex-end; margin-top: 24px;">
                            <button type="button" onclick="cerrarModal('modalEditar{{ $pregunta->id }}')" class="btn btn--ghost" style="color: var(--black); border-color: var(--gray-400);">Cancelar</button>
                            <button type="submit" class="btn btn--primary">Actualizar</button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    <div style="margin-top: 32px;">
        {{ $preguntas->links() }}
    </div>
</div>

<!-- Modal para Crear animado -->
<div id="modalCrear" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000;">
    <div class="card modal-content" style="position: relative; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 500px; box-shadow: var(--shadow-lg);">
        <h3 class="card__title">Nueva Pregunta</h3>
        <form method="POST" action="{{ route('soporte.store') }}" class="form" style="margin-top: 20px;">
            @csrf
            <div class="field">
                <label>Pregunta</label>
                <input type="text" name="pregunta" required placeholder="Ej: ¿Cómo restablezco mi contraseña?">
            </div>
            <div class="field">
                <label>Respuesta</label>
                <textarea name="respuesta" required placeholder="Detalla la solución..."></textarea>
            </div>
            <div class="actions" style="justify-content: flex-end; margin-top: 24px;">
                <button type="button" onclick="cerrarModal('modalCrear')" class="btn btn--ghost" style="color: var(--black); border-color: var(--gray-400);">Cancelar</button>
                <button type="submit" class="btn btn--primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Personalizado para Eliminar -->
<div id="modalConfirmarEliminar" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000;">
    <div class="card modal-content" style="position: relative; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 400px; box-shadow: var(--shadow-lg); border-top: 5px solid var(--red-dark);">
        <h3 class="card__title" style="color: var(--red-dark);">Eliminar Pregunta</h3>
        <p style="color: var(--gray-700); margin-top: 10px;">¿Estás totalmente seguro de que deseas eliminar esta pregunta? Esta acción no se puede deshacer.</p>
        
        <div class="actions" style="justify-content: flex-end; margin-top: 24px;">
            <button type="button" onclick="cerrarModal('modalConfirmarEliminar')" class="btn btn--ghost" style="color: var(--black); border-color: var(--gray-400);">Cancelar</button>
            <button type="button" onclick="ejecutarEliminacion()" class="btn btn--danger">Sí, Eliminar</button>
        </div>
    </div>
</div>

<!-- Lógica de Interacción JS -->
<script>
    let modoActual = 'normal'; 
    let idParaEliminar = null; // Guarda temporalmente el ID de la pregunta a borrar

    function activarModo(modo) {
        let barraEstado = document.getElementById('barraEstado');
        let btnEditar = document.getElementById('btnModoEditar');
        let btnEliminar = document.getElementById('btnModoEliminar');

        if (modoActual === modo) {
            modoActual = 'normal';
            barraEstado.style.display = 'none';
            btnEditar.style.border = 'none';
            btnEliminar.style.border = '1.5px solid var(--gray-400)';
            return;
        }

        modoActual = modo;
        barraEstado.style.display = 'block';

        if (modo === 'editar') {
            barraEstado.textContent = 'Modo Edición: Haz clic en una pregunta para editarla.';
            barraEstado.className = 'alert alert--pendiente';
            btnEditar.style.border = '2px solid var(--red)';
            btnEliminar.style.border = '1.5px solid var(--gray-400)';
        } else if (modo === 'eliminar') {
            barraEstado.textContent = 'Modo Eliminación: Haz clic en una pregunta para borrarla.';
            barraEstado.className = 'alert alert--error';
            btnEliminar.style.border = '2px solid var(--red-dark)';
            btnEditar.style.border = 'none';
        }
    }

    function interactuarElemento(event, id) {
        if (modoActual === 'normal') return; 

        event.preventDefault(); // Evita que el acordeón se despliegue

        if (modoActual === 'editar') {
            document.getElementById('modalEditar' + id).style.display = 'block';
            activarModo('normal'); 
        } else if (modoActual === 'eliminar') {
            idParaEliminar = id; // Guardamos el ID
            document.getElementById('modalConfirmarEliminar').style.display = 'block';
            activarModo('normal'); 
        }
    }

    function ejecutarEliminacion() {
        if(idParaEliminar) {
            document.getElementById('form-eliminar-' + idParaEliminar).submit();
        }
    }

    function cerrarModal(idModal) {
        document.getElementById(idModal).style.display = 'none';
    }
</script>

<!-- Estilos para Animaciones y Flechas -->
<style>
    /* Ocultar flecha nativa */
    details > summary::-webkit-details-marker {
        display: none;
    }
    
    /* Animación de la flecha al abrir/cerrar */
    .icono-flecha {
        transition: transform 0.3s ease-in-out;
    }
    details[open] .icono-flecha {
        transform: rotate(180deg);
        stroke: var(--red); /* Se pone roja al abrirse */
    }

    /* Animación del contenido del acordeón */
    details[open] .acordeon-contenido {
        animation: deslizarAbajo 0.3s ease-out forwards;
    }
    @keyframes deslizarAbajo {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Animaciones de los Modales */
    .modal-overlay {
        animation: aparecerFondo 0.2s ease-out;
    }
    .modal-content {
        animation: escalarModal 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); /* Efecto rebote suave */
    }
    
    @keyframes aparecerFondo {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes escalarModal {
        from { opacity: 0; transform: translate(-50%, -60%) scale(0.9); }
        to { opacity: 1; transform: translate(-50%, -50%) scale(1); }
    }
</style>
@endsection