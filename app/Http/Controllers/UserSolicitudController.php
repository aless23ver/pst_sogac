<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Solicitud;
use App\Models\Documentacion;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class UserSolicitudController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Validación de entradas
        $request->validate([
            'tipo_solicitud' => 'required|exists:tipo_solicitudes,tsi_id',
            'motivo' => 'required|string',
            'documentos.*' => 'nullable|file|max:2048'
        ]);

        // 2. Creación del registro central
        $nuevaSolicitud = Solicitud::create([
            'sol_usu_id' => Auth::id(),
            'sol_tsi_id' => $request->tipo_solicitud,
            'sol_lac_id' => 1, // ID del Lapso académico actual
            'sol_eso_id' => 1, // 1 = Estado "Pendiente" por defecto
            'sol_id_seguimiento' => 'REQ-' . strtoupper(Str::random(6)),
            'sol_motivo_detallado' => $request->motivo,
        ]);

        // 3. Procesamiento de archivos adjuntos
        if ($request->hasFile('documentos')) {
            foreach ($request->file('documentos') as $req_id => $archivo) {
                $ruta = $archivo->store('solicitudes_adjuntos', 'public');
                
                Documentacion::create([
                    'doc_sol_id' => $nuevaSolicitud->sol_id,
                    'doc_nombre_original_archivo' => $archivo->getClientOriginalName(),
                    'doc_ruta_almacenamiento_url' => $ruta,
                ]);
            }
        }

        // 4. Salida: Notificación de éxito
        return redirect()->route('user.dashboard')
            ->with('success', 'Tu solicitud fue aprobada y procesada. Código de seguimiento: ' . $nuevaSolicitud->sol_id_seguimiento);
    }

    /**
     * Display the specified resource.
     */
    public function show()
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update()
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy()
    {
        //
    }
}


