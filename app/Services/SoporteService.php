<?php

namespace App\Services;

use App\Mail\NuevoMensajeSoporte;
use App\Models\ChatSoporte\HiloChat;
use App\Models\ChatSoporte\MensajeChat;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SoporteService
{
    public function iniciarChat($usuarioId, $cuerpo = null, $archivoImagen = null)
    {
        $chatAbierto = HiloChat::where('hch_id_usuario', $usuarioId)
            ->whereIn('hch_estado', ['pendiente', 'activo', 'pendiente_cierre'])
            ->first();

        if ($chatAbierto) {
            throw new Exception('Ya tienes una consulta de soporte en curso.', 409);
        }

        // Usamos una transacción para asegurar que Hilo y Mensaje se creen juntos o ninguno se cree
        return DB::transaction(function () use ($usuarioId, $cuerpo, $archivoImagen) {

            // 1. Creamos el hilo
            $hilo = HiloChat::create([
                'hch_id_usuario' => $usuarioId,
                'hch_estado' => 'pendiente',
            ]);

            // 2. Si el usuario envió un texto o imagen, creamos el primer mensaje usando tu otra función
            if ($cuerpo || $archivoImagen) {
                $this->enviarMensaje($hilo->hch_id, $usuarioId, $cuerpo, $archivoImagen);
            }

            return $hilo;
        });
    }

    public function enviarMensaje($hiloId, $remitenteId, $cuerpo, $archivoImagen = null)
    {
        $rutaImagen = null;

        if ($archivoImagen) {
            $rutaImagen = $archivoImagen->store('chats', 'public');
        }

        $mensaje = MensajeChat::create([
            'mch_id_hilo' => $hiloId,
            'mch_id_remitente' => $remitenteId,
            'mch_cuerpo' => $cuerpo,
            'mch_ruta_imagen' => $rutaImagen,
        ]);

        // --- NUEVA LÓGICA DE CORREO ---
        // Cargamos el hilo con los datos de su creador
        $hilo = HiloChat::with('usuario')->find($hiloId);

        // Si el que envía el mensaje NO es el dueño del ticket (es decir, es el admin)
        if ($hilo && $hilo->hch_id_usuario !== $remitenteId) {
            Mail::to($hilo->usuario->usu_correo_electronico)->send(new NuevoMensajeSoporte($hilo->hch_id));
        }

        return $mensaje;
    }

    public function reclamarChat($hiloId, $adminId)
    {
        $hilo = HiloChat::findOrFail($hiloId);

        if ($hilo->hch_estado !== 'pendiente') {
            throw new Exception('Este chat ya fue reclamado por otro administrador o ya está cerrado.');
        }

        $hilo->update([
            'hch_estado' => 'activo',
            'hch_id_admin' => $adminId,
        ]);

        return $hilo;
    }

    /**
     * Sube el chat un nivel de atención y lo devuelve a la bandeja como
     * pendiente.
     *
     * Puede pedirlo el estudiante (necesita hablar con alguien más arriba) o
     * el personal que lo atiende (no puede resolverlo). Al escalar se libera
     * al administrador asignado: los roles de nivel inferior dejan de ver el
     * chat y lo reclama uno del nivel nuevo o superior.
     *
     * **Nuevo flujo**: el escalado queda en estado "pending_escalation"
     * y requiere aprobación del administrador antes de aplicarse.
     */
    public function escalarChat($hiloId, ?int $actorId, bool $loPideElEstudiante)
    {
        $hilo = HiloChat::findOrFail($hiloId);

        if ($hilo->hch_estado === 'cerrado') {
            throw new Exception('Este chat ya está cerrado: no se puede escalar.');
        }

        if ($hilo->hch_estado === 'pending_escalation') {
            throw new Exception('Este chat ya está pendiente de aprobación de escalado.');
        }

        if (! $hilo->puedeSolicitarEscalarPor($loPideElEstudiante ? 'estudiante' : 'personal')) {
            throw new Exception('No puedes solicitar el escalado de este chat en su estado actual.');
        }

        // Pone el chat en estado de escalado pendiente de aprobación.
        // El nivel actual se conserva y el admin decidirá subir o no.
        return DB::transaction(function () use ($hilo, $actorId, $loPideElEstudiante) {
            $hilo->update([
                'hch_estado' => 'pending_escalation',
            ]);

            // Registra la solicitud de escalado dentro del chat, para que el
            // administrador vea de quién viene y en qué nivel estaba.
            MensajeChat::create([
                'mch_id_hilo' => $hilo->hch_id,
                'mch_id_remitente' => $actorId,
                'mch_cuerpo' => $loPideElEstudiante
                    ? 'Solicitud de escalado: el estudiante quiere hablar con alguien más arriba (actualmente en nivel '
                        .$hilo->nivel_etiqueta.').'
                    : 'Solicitud de escalado: el personal deriva esta consulta al siguiente nivel (actualmente en nivel '
                        .$hilo->nivel_etiqueta.').',
            ]);

            return $hilo;
        });
    }

    /**
     * Cierre forzado por conducta inadecuada: lo aplica el administrador sin
     * la confirmación del estudiante y deja el motivo como mensaje dentro
     * del chat, para que quede constancia de por qué se cerró.
     */
    public function cerrarForzado($hiloId, int $adminId, string $motivo)
    {
        $hilo = HiloChat::findOrFail($hiloId);

        if ($hilo->hch_estado === 'cerrado') {
            throw new Exception('Este chat ya está cerrado.');
        }

        return DB::transaction(function () use ($hilo, $adminId, $motivo) {
            $hilo->update([
                'hch_estado' => 'cerrado',
                'hch_id_admin' => $hilo->hch_id_admin ?? $adminId,
                'hch_etiqueta_tema' => 'Cierre forzado',
                'hch_fecha_solicitud_cierre' => now(),
            ]);

            MensajeChat::create([
                'mch_id_hilo' => $hilo->hch_id,
                'mch_id_remitente' => $adminId,
                'mch_cuerpo' => 'La coordinación cerró esta consulta por conducta inadecuada. Motivo: '.$motivo,
            ]);

            return $hilo;
        });
    }

    /**
     * Aplica la escalación aprobada por el administrador.
     *
     * Sube el nivel en uno, cambia el estado a 'pendiente' y libera al
     * administrador asignado, para que el chat quede en la bandeja del
     * nuevo nivel.
     */
    public function aprobarEscalado($hiloId, int $adminId)
    {
        $hilo = HiloChat::findOrFail($hiloId);

        if ($hilo->hch_estado !== 'pending_escalation') {
            throw new Exception('Este chat no está pendiente de aprobación de escalado.');
        }

        if ($hilo->puedeEscalar()) {
            throw new Exception('Este chat ya puede escalarse: no está en estado de pending_escalation.');
        }

        $nivelAnterior = $hilo->hch_nivel_atencion;

        return DB::transaction(function () use ($hilo, $adminId, $nivelAnterior) {
            $hilo->update([
                'hch_nivel_atencion' => $nivelAnterior + 1,
                'hch_estado' => 'pendiente',
                'hch_id_admin' => null,
            ]);

            // La aprobación y nuevo nivel quedan registradas en el chat.
            MensajeChat::create([
                'mch_id_hilo' => $hilo->hch_id,
                'mch_id_remitente' => $adminId,
                'mch_cuerpo' => 'Escalado aprobado por la coordinación: sube de nivel '.(self::NIVELES_ATENCION[$nivelAnterior] ?? $nivelAnterior).' a '
                    .(self::NIVELES_ATENCION[$hilo->hch_nivel_atencion] ?? $hilo->hch_nivel_atencion).'.',
            ]);

            return $hilo;
        });
    }

    /**
     * Rechaza la solicitud de escalado y restaura el chat a su estado anterior.
     */
    public function rechazarEscalado($hiloId, int $adminId, string $motivo)
    {
        $hilo = HiloChat::findOrFail($hiloId);

        if ($hilo->hch_estado !== 'pending_escalation') {
            throw new Exception('Este chat no está pendiente de aprobación de escalado.');
        }

        return DB::transaction(function () use ($hilo, $adminId, $motivo) {
            $hilo->update([
                'hch_estado' => 'pendiente',
            ]);

            MensajeChat::create([
                'mch_id_hilo' => $hilo->hch_id,
                'mch_id_remitente' => $adminId,
                'mch_cuerpo' => 'Solicitud de escalado rechazada por la coordinación. Motivo: '.$motivo,
            ]);

            return $hilo;
        });
    }

    public function proponerCierre($hiloId, $adminId, $etiqueta)
    {
        $hilo = HiloChat::findOrFail($hiloId);

        if ($hilo->hch_estado !== 'activo') {
            throw new Exception('Este chat no se puede cerrar porque no está activo.');
        }

        if ($hilo->hch_id_admin !== $adminId) {
            throw new Exception('Solo el administrador que está atendiendo este chat puede proponer su cierre.');
        }

        $hilo->update([
            'hch_estado' => 'pendiente_cierre',
            'hch_etiqueta_tema' => $etiqueta,
            'hch_fecha_solicitud_cierre' => now(),
        ]);

        return $hilo;
    }

    public function cambiarEstadoConfirmacion($hiloId, $usuarioId, $confirmado)
    {
        $hilo = HiloChat::findOrFail($hiloId);

        if ($hilo->hch_id_usuario !== $usuarioId) {
            throw new Exception('No tienes permiso para modificar el estado de este chat.');
        }

        if ($hilo->hch_estado !== 'pendiente_cierre') {
            throw new Exception('Este chat no está esperando confirmación de cierre.');
        }

        if ($confirmado) {
            $hilo->update(['hch_estado' => 'cerrado']);
        } else {
            $hilo->update([
                'hch_estado' => 'activo',
                'hch_fecha_solicitud_cierre' => null,
                'hch_etiqueta_tema' => null,
            ]);
        }

        return $hilo;
    }

    public function obtenerHistorialPaginado($usuario, $porPagina = 15)
    {
        $query = HiloChat::where('hch_estado', 'cerrado')->orderBy('updated_at', 'desc');

        if ($usuario->esAdministrativo()) {
            return $query->with(['usuario', 'admin'])->paginate($porPagina);
        }

        return $query->with(['admin'])
            ->where('hch_id_usuario', $usuario->usu_id)
            ->paginate($porPagina);
    }

    public function obtenerEstadoBandejaSoporte($usuario)
    {
        $estado = [
            'hilosPendientes' => collect(),
            'hilosActivosAdmin' => collect(),
            'hiloActivo' => null,
        ];

        if ($usuario->esAdministrativo()) {
            // Cada rol ve los pendientes de SU nivel o inferiores: los chats
            // nuevos (nivel 1) los puede reclamar cualquiera de la cola, pero
            // uno escalado a analista (2) ya no aparece para el taquillero.
            $nivelMaximo = HiloChat::nivelRequeridoPorRol($usuario->usu_rol);

            $estado['hilosPendientes'] = HiloChat::with('usuario')
                ->where('hch_estado', 'pendiente')
                ->where('hch_nivel_atencion', '<=', $nivelMaximo)
                ->orderBy('created_at', 'asc')
                ->get();

            $estado['hilosActivosAdmin'] = HiloChat::with('usuario')
                ->where('hch_id_admin', $usuario->usu_id)
                ->whereIn('hch_estado', ['activo', 'pendiente_cierre'])
                ->orderBy('updated_at', 'desc')
                ->get();
        } else {
            $estado['hiloActivo'] = HiloChat::where('hch_id_usuario', $usuario->usu_id)
                ->whereIn('hch_estado', ['pendiente', 'activo', 'pendiente_cierre'])
                ->first();
        }

        return $estado;
    }

    public function obtenerChatConMensajes($hiloId)
    {
        return HiloChat::with(['mensajes.remitente', 'usuario', 'admin'])->findOrFail($hiloId);
    }
}
