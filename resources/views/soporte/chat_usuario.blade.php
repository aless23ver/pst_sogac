@extends('layouts.plantilla_soporte')

@section('title', 'Mi Consulta')

@section('content')
<div class="container" style="padding: 2rem 0;">
    <h2>Chat de Soporte (Modo Usuario)</h2>
    <p>Estado del ticket: <strong>{{ $hilo->hch_estado }}</strong></p>

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

    @if($hilo->hch_estado === 'pendiente_cierre')
        <div style="background: #fff3cd; padding: 15px; border: 1px solid #ffeeba; border-radius: 8px;">
            <h4 style="margin-top: 0;">Confirmación requerida</h4>
            <p>El administrador ha propuesto cerrar este chat. ¿Tu problema fue resuelto?</p>
            <form action="{{ route('chat.confirmar', $hilo->hch_id) }}" method="POST">
                @csrf
                <button style="background: #28a745; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">Sí, confirmar cierre</button>
            </form>
            <form action="{{ route('chat.rechazar', $hilo->hch_id) }}" method="POST">
                @csrf
                <button style="background: #dc3545; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">No, aún necesito ayuda</button>
            </form>
        </div>
    @elseif($hilo->hch_estado === 'cerrado')
        <div style="background: #e2e3e5; padding: 15px; border-radius: 8px; text-align: center;">
            <p style="margin: 0;">Este chat ha sido cerrado.</p>
        </div>
    @else
        <form action="{{ route('chat.enviar', $hilo->hch_id) }}" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 10px;">
            @csrf
            <textarea name="mch_cuerpo" rows="3" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc;" placeholder="Escribe tu mensaje aquí..."></textarea>
            <div>
                <label style="font-weight: bold; font-size: 0.9rem;">Adjuntar imagen (Opcional):</label><br>
                <input type="file" name="imagen" accept="image/*">
            </div>
            <button type="submit" style="background: #007bff; color: white; border: none; padding: 10px; border-radius: 8px; cursor: pointer; font-weight: bold; width: 150px;">Enviar Mensaje</button>
        </form>
    @endif
</div>
@endsection