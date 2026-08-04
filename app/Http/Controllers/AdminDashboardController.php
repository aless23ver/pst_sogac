<?php

namespace App\Http\Controllers;

use App\Models\Solicitud;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Obtener estadísticas usando Eloquent
        $stats = [
            'pendiente' => Solicitud::where('estado', 'pendiente')->count(),
            'aprobada'  => Solicitud::where('estado', 'aprobada')->count(),
            'rechazada' => Solicitud::where('estado', 'rechazada')->count(),
        ];
        $total = array_sum($stats);

        // Obtener solicitudes junto con los datos del usuario asociado (Eager Loading para reemplazar el JOIN)
        $solicitudes = Solicitud::with('usuario')->orderBy('creado_en', 'desc')->get();

        return view('admin.dashboard', compact('stats', 'total', 'solicitudes'));
    }

    public function cambiarEstado(Solicitud $solicitud, $accion)
    {
        if ($accion === 'aprobar') {
            $solicitud->update(['estado' => 'aprobada']);
            $mensaje = 'La solicitud ha sido aprobada con éxito.';
        } elseif ($accion === 'rechazar') {
            $solicitud->update(['estado' => 'rechazada']);
            $mensaje = 'La solicitud ha sido rechazada.';
        } else {
            return redirect()->route('admin.dashboard');
        }

        // Redirigimos de vuelta enviando un mensaje de éxito temporal a la sesión (Flash Data)
        return redirect()->route('admin.dashboard')->with('success', $mensaje);
    }
}