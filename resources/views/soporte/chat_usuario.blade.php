@extends('layouts.plantilla_soporte')

@section('title', 'Mi Consulta')

@section('content')
<div class="container" style="padding: 2rem 0;">
    <h2>Chat de Soporte (Modo Usuario)</h2>
    <p>Estado del ticket: <strong>{{ $hilo->hch_estado }}</strong></p>

    <!-- Caja de mensajes -->
    <div style="border: 1px solid #ccc; height: 350px; overflow-y: auto; padding: 15px; margin-bottom: 20px; background: #fff; border-radius: 8px;">
        <div style="background: #f1f3f5; padding: 10px; margin-bottom: 10px; border-radius: 8px 8px 8px 0; width: fit-content;">
            <strong>Admin:</strong> Hola, ¿en qué te puedo ayudar hoy?
        </div>
        <div style="background: #e3f2fd; padding: 10px; margin-bottom: 10px; border-radius: 8px 8px 0 8px; width: fit-content; margin-left: auto;">
            <strong>Tú:</strong> Tengo un problema con mi registro.
        </div>
    </div>

    @if($hilo->hch_estado === 'pendiente_cierre')
        <div style="background: #fff3cd; padding: 15px; border: 1px solid #ffeeba; border-radius: 8px;">
            <h4 style="margin-top: 0;">Confirmación requerida</h4>
            <p>El administrador ha propuesto cerrar este chat. ¿Tu problema fue resuelto?</p>
            <button style="background: #28a745; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">Sí, confirmar cierre</button>
            <button style="background: #dc3545; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">No, aún necesito ayuda</button>
        </div>
    @elseif($hilo->hch_estado === 'cerrado')
        <div style="background: #e2e3e5; padding: 15px; border-radius: 8px; text-align: center;">
            <p style="margin: 0;">Este chat ha sido cerrado.</p>
        </div>
    @else
        <form action="#" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 10px;">
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