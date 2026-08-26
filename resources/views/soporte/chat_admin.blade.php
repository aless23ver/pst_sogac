@extends('layouts.plantilla_soporte')

@section('title', 'Atender Ticket')

@section('content')
<div class="container" style="padding: 2rem 0; display: flex; gap: 20px; flex-wrap: wrap;">
    
    <!-- Columna Izquierda: El Chat -->
    <div style="flex: 1; min-width: 300px;">
        <h2>Atendiendo Ticket #{{ $hilo->hch_id }}</h2>
        <p>Estado actual: <strong>{{ $hilo->hch_estado }}</strong></p>

        <!-- Caja dinámica de mensajes -->
        <div style="border: 1px solid #ccc; height: 350px; overflow-y: auto; padding: 15px; margin-bottom: 20px; background: #fff; border-radius: 8px; display: flex; flex-direction: column;">
            
            @forelse($hilo->mensajes as $mensaje)
                @if($mensaje->mch_id_remitente === auth()->id())
                    <!-- Mensaje propio (Alineado a la derecha) -->
                    <div style="background: #e3f2fd; padding: 10px; margin-bottom: 10px; border-radius: 8px 8px 0 8px; width: fit-content; max-width: 80%; align-self: flex-end;">
                        <strong style="color: #0056b3;">Tú:</strong><br>
                        <span style="white-space: pre-line;">{{ $mensaje->mch_cuerpo }}</span>
                        
                        <!-- Si hay imagen, la mostramos -->
                        @if($mensaje->mch_ruta_imagen)
                            <br><img src="{{ asset('storage/' . $mensaje->mch_ruta_imagen) }}" alt="Evidencia adjunta" style="max-width: 100%; margin-top: 10px; border-radius: 4px; border: 1px solid #b8daff;">
                        @endif
                        
                        <div style="font-size: 0.7rem; color: #6c757d; text-align: right; margin-top: 5px;">
                            {{ $mensaje->created_at->format('d/m/Y h:i A') }}
                        </div>
                    </div>
                @else
                    <!-- Mensaje de la otra persona (Alineado a la izquierda) -->
                    <div style="background: #f1f3f5; padding: 10px; margin-bottom: 10px; border-radius: 8px 8px 8px 0; width: fit-content; max-width: 80%; align-self: flex-start;">
                        <strong style="color: #495057;">{{ $mensaje->remitente->usu_primer_nombre ?? 'Usuario' }}:</strong><br>
                        <span style="white-space: pre-line;">{{ $mensaje->mch_cuerpo }}</span>
                        
                        <!-- Si hay imagen, la mostramos -->
                        @if($mensaje->mch_ruta_imagen)
                            <br><img src="{{ asset('storage/' . $mensaje->mch_ruta_imagen) }}" alt="Evidencia adjunta" style="max-width: 100%; margin-top: 10px; border-radius: 4px; border: 1px solid #dee2e6;">
                        @endif
                        
                        <div style="font-size: 0.7rem; color: #6c757d; margin-top: 5px;">
                            {{ $mensaje->created_at->format('d/m/Y h:i A') }}
                        </div>
                    </div>
                @endif
            @empty
                <!-- Si no hay mensajes todavía -->
                <p style="text-align: center; color: #6c757d; margin-top: auto; margin-bottom: auto;">
                    No hay mensajes en este chat aún. ¡Escribe el primero!
                </p>
            @endforelse

        </div>

        @if($hilo->hch_estado === 'activo')
            <form action="{{ route('chat.enviar', $hilo->hch_id) }}" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 10px;">
                @csrf
                <textarea name="mch_cuerpo" rows="3" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc;" placeholder="Escribe la respuesta al usuario..."></textarea>
                <div>
                    <input type="file" name="imagen" accept="image/*">
                </div>
                <button type="submit" style="background: #007bff; color: white; border: none; padding: 10px; border-radius: 8px; cursor: pointer; font-weight: bold; width: 150px;">Enviar Respuesta</button>
            </form>
        @endif
    </div>

    <!-- Columna Derecha: Panel de Acciones -->
    <div style="width: 300px;">
        @if($hilo->hch_estado === 'activo')
            <div style="background: #e9ecef; padding: 20px; border-radius: 8px; border: 1px solid #dee2e6;">
                <h3 style="margin-top: 0;">Acciones de Admin</h3>
                <p style="font-size: 0.9rem;">Si el problema fue resuelto, puedes proponer el cierre del ticket.</p>
                <form action="{{ route('chat.proponer_cierre', $hilo->hch_id) }}" method="POST" style="display: flex; flex-direction: column; gap: 10px;">
                    <label style="font-weight: bold; font-size: 0.9rem;">Etiqueta del tema:</label>
                    <input type="text" name="etiqueta_tema" placeholder="Ej: Error de sistema" required style="padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                    <button type="submit" style="background: #ffc107; color: #000; border: none; padding: 10px; border-radius: 8px; cursor: pointer; font-weight: bold;">Solicitar Cierre</button>
                </form>
            </div>
        @elseif($hilo->hch_estado === 'pendiente_cierre')
            <div style="background: #e9ecef; padding: 20px; border-radius: 8px; border: 1px solid #dee2e6; text-align: center;">
                <p>Esperando a que el usuario confirme el cierre del ticket...</p>
            </div>
        @else
            <div style="background: #d4edda; padding: 20px; border-radius: 8px; border: 1px solid #c3e6cb; text-align: center; color: #155724;">
                <p style="margin: 0;"><strong>Ticket cerrado</strong></p>
            </div>
        @endif
    </div>

</div>
@endsection