<?php

namespace App\Http\Controllers;

use App\Models\Solicitud;
use App\Models\EstadoSolicitud;
use App\Models\HistorialEstadoSolicitud; // ¡Bonus para tu proyecto!
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    // Mostrar estadísticas y tabla (Equivale a los SELECT del inicio)
    public function index()
    {
        // Traemos las solicitudes con sus relaciones (Eager Loading para evitar el N+1 y los JOINs manuales)
        $solicitudes = Solicitud::with(['usuario', 'tipoSolicitud', 'estadoActual'])
                                ->orderBy('sol_fecha_creacion', 'DESC')
                                ->get();

        // Calculamos las estadísticas directamente desde la colección de Laravel
        $stats = [
            'pendiente' => $solicitudes->where('estadoActual.eso_nombre_estado', 'pendiente')->count(),
            'aprobada'  => $solicitudes->where('estadoActual.eso_nombre_estado', 'aprobada')->count(),
            'rechazada' => $solicitudes->where('estadoActual.eso_nombre_estado', 'rechazada')->count(),
            'total'     => $solicitudes->count(),
        ];

        return view('admin.dashboard', compact('solicitudes', 'stats'));
    }

    // Cambiar estado (Equivale a action=aprobar o action=rechazar)
    public function cambiarEstado($id, $accion)
    {
        // $accion puede ser 'aprobar' o 'rechazar'
        $solicitud = Solicitud::findOrFail($id);
        
        // 1. Buscamos el ID del estado destino en la tabla estado_solicitudes
        $nombreEstado = ($accion === 'aprobar') ? 'aprobada' : 'rechazada';
        $estadoNuevo = EstadoSolicitud::where('eso_nombre_estado', $nombreEstado)->firstOrFail();

        // Bonus: Guardar el historial antes de cambiarlo (Opcional pero recomendado para tu sistema)
        HistorialEstadoSolicitud::create([
            'hes_sol_id' => $solicitud->sol_id,
            'hes_usu_id_responsable' => auth()->user()->usu_id ?? 1, // ID del admin autenticado
            'hes_eso_id_anterior' => $solicitud->sol_eso_id,
            'hes_eso_id_nuevo' => $estadoNuevo->eso_id,
        ]);

        // 2. Actualizamos la solicitud con el nuevo ID de estado
        $solicitud->update([
            'sol_eso_id' => $estadoNuevo->eso_id
        ]);

        $mensaje = ($accion === 'aprobar') 
            ? 'La solicitud ha sido aprobada con éxito.' 
            : 'La solicitud ha sido rechazada.';

        return redirect()->route('admin.dashboard')->with('success', $mensaje);
    }
}