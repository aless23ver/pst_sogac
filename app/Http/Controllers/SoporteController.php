<?php

namespace App\Http\Controllers;

use App\Models\ChatSoporte\HiloChat;
use App\Services\SoporteService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SoporteController extends Controller
{
    protected $soporteService;

    public function __construct(SoporteService $soporteService)
    {
        $this->soporteService = $soporteService;
    }

    public function iniciarChat(Request $request)
    {
        // 1. El controlador valida que los datos HTTP sean correctos
        $request->validate([
            'mch_cuerpo' => 'required_without:imagen|string|nullable',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'mch_cuerpo.required_without' => 'Debes describir tu problema o adjuntar una imagen para abrir un nuevo ticket.',
        ]);

        try {
            // 2. Le pasamos los datos limpios al Servicio
            $nuevoHilo = $this->soporteService->iniciarChat(
                Auth::id(),
                $request->mch_cuerpo,
                $request->file('imagen')
            );

            return redirect()->route('user.ayuda.chat.mostrar', $nuevoHilo->hch_id);

        } catch (Exception $e) {
            if ($e->getCode() == 409) {
                $chatAbierto = HiloChat::where('hch_id_usuario', Auth::id())
                    ->whereIn('hch_estado', ['pendiente', 'activo', 'pendiente_cierre'])
                    ->first();

                return redirect()->route('user.ayuda.chat.mostrar', $chatAbierto->hch_id)
                    ->with('info', $e->getMessage());
            }

            return back()->with('error', 'Ocurrió un error al iniciar el chat.');
        }
    }

    public function enviarMensaje(Request $request, $hch_id)
    {
        $request->validate([
            'mch_cuerpo' => 'nullable|string',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if (! $request->mch_cuerpo && ! $request->hasFile('imagen')) {
            return back()->with('error', 'Debes escribir un mensaje o adjuntar una imagen.');
        }

        $this->soporteService->enviarMensaje(
            $hch_id,
            Auth::id(),
            $request->mch_cuerpo,
            $request->file('imagen')
        );

        return back();
    }

    public function reclamarChat($hch_id)
    {
        try {
            $hilo = $this->soporteService->reclamarChat($hch_id, Auth::id());

            return redirect()->route('admin.chat.mostrar', $hilo->hch_id)
                ->with('success', 'Has reclamado esta ayuda. Ahora puedes ayudar al usuario.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function proponerCierre(Request $request, $hch_id)
    {
        $request->validate([
            'etiqueta_tema' => 'required|string|max:100',
        ], [
            'etiqueta_tema.required' => 'Debes escribir una etiqueta descriptiva para cerrar el chat.',
        ]);

        try {
            $this->soporteService->proponerCierre($hch_id, Auth::id(), $request->etiqueta_tema);

            return back()->with('success', 'Has propuesto el cierre del chat. El usuario tiene 7 días para confirmar.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Solicita el escalado del chat. Puede pedirlo el estudiante (quiere
     * hablar con alguien más arriba) o el personal que lo atiende (no puede
     * resolverlo). El chat queda en estado "pending_escalation" y requiere
     * aprobación del administrador antes de aplicarse.
     */
    public function escalarChat($hch_id)
    {
        $usuario = Auth::user();

        try {
            $hilo = $this->soporteService->escalarChat(
                $hch_id,
                $usuario->usu_id,
                ! $usuario->esAdministrativo(),
            );

            // Si el chat quedó pendiente de aprobación, redirect al index
            // con un aviso; si ya fue aplicado, redirige según el rol.
            if ($hilo->hch_estado === 'pending_escalation') {
                return redirect()
                    ->route('admin.chat.index')
                    ->with('info', 'Solicitud de escalado enviada. El administrador la revisará y aplicará.');
            }

            // Si el escalado ya se aplicó (estado cambiado), redirige normal.
            return redirect()->route(
                $usuario->esAdministrativo() ? 'admin.chat.index' : 'user.ayuda.chat.mostrar',
                $usuario->esAdministrativo() ? [] : [$hilo->hch_id],
            )->with('success', 'El chat fue escalado al nivel '.strtolower($hilo->nivel_etiqueta).'.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Aplica la escalación aprobada por el administrador.
     */
    public function aprobarEscalado($hch_id)
    {
        if (! Auth::user()->esAdministrador()) {
            abort(403, 'Solo el administrador puede aprobar el escalado de un chat.');
        }

        try {
            $this->soporteService->aprobarEscalado($hch_id, Auth::id());

            return back()->with('success', 'Escalado aprobado. El chat sube un nivel y vuelve a la bandeja correspondiente.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Rechaza la solicitud de escalado y restaura el chat a su estado anterior.
     */
    public function rechazarEscalado(Request $request, $hch_id)
    {
        if (! Auth::user()->esAdministrador()) {
            abort(403, 'Solo el administrador puede rechazar el escalado de un chat.');
        }

        $request->validate([
            'motivo' => 'required|string|max:500',
        ], [
            'motivo.required' => 'Escribe el motivo por el cual se rechaza el escalado.',
        ]);

        try {
            $this->soporteService->rechazarEscalado($hch_id, Auth::id(), trim((string) $request->input('motivo')));

            return back()->with('success', 'Solicitud de escalado rechazada. El chat permanece en su nivel actual.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cierre forzado por conducta inadecuada: solo el administrador, con
     * motivo, sin esperar la confirmación del estudiante.
     */
    public function cerrarForzado(Request $request, $hch_id)
    {
        $request->validate([
            'motivo' => 'required|string|max:500',
        ], [
            'motivo.required' => 'Escribe el motivo del cierre forzado: queda como constancia en el chat.',
        ]);

        if (! Auth::user()->esAdministrador()) {
            abort(403, 'Solo el administrador puede cerrar un chat de forma forzada.');
        }

        try {
            $this->soporteService->cerrarForzado($hch_id, Auth::id(), trim((string) $request->input('motivo')));

            return back()->with('success', 'Chat cerrado de forma forzada. El motivo quedó registrado en la conversación.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function confirmarCierre($hch_id)
    {
        try {
            $this->soporteService->cambiarEstadoConfirmacion($hch_id, Auth::id(), true);

            return back()->with('success', 'Has confirmado la solución. El chat ha sido cerrado.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function desconfirmarCierre($hch_id)
    {
        try {
            $this->soporteService->cambiarEstadoConfirmacion($hch_id, Auth::id(), false);

            return back()->with('success', 'Has rechazado la confirmación. Puedes seguir con la conversación.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function verHistorial()
    {
        $usuario = Auth::user();

        $hilos = $this->soporteService->obtenerHistorialPaginado($usuario);
        $estadoBandeja = $this->soporteService->obtenerEstadoBandejaSoporte($usuario);

        // Bandejas separadas por rol: el admin prioriza los tickets sin
        // reclamar y el estudiante ve su consulta en curso. Antes vivía todo
        // en una sola vista que se bifurcaba con @if sobre el rol.
        $vista = $usuario->esAdministrativo() ? 'admin.chat.index' : 'ayuda.chat.index';

        return view($vista, array_merge(['hilos' => $hilos], $estadoBandeja));
    }

    public function mostrarChat($hch_id)
    {
        $hilo = $this->soporteService->obtenerChatConMensajes($hch_id);

        $esAdmin = Auth::user()->esAdministrativo();

        if (! $esAdmin && $hilo->hch_id_usuario !== Auth::id()) {
            abort(403, 'No tienes permiso para ver este chat.');
        }

        return $esAdmin
            ? view('admin.chat.mostrar', compact('hilo'))
            : view('ayuda.chat.mostrar', compact('hilo'));
    }
}
