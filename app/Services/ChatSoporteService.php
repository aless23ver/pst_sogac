<?php

namespace App\Services;

use App\Models\ChatSoporte\HiloChat;
use App\Models\ChatSoporte\MensajeChat;
use Exception;

class SoporteService
{
    public function iniciarChat($usuarioId)
    {
        $chatAbierto = HiloChat::where('hch_id_usuario', $usuarioId)
            ->whereIn('hch_estado', ['pendiente', 'activo', 'pendiente_cierre'])
            ->first();

        if ($chatAbierto) {
            throw new Exception('Ya tienes una consulta de soporte en curso.', 409);
        }

        return HiloChat::create([
            'hch_id_usuario' => $usuarioId,
            'hch_estado' => 'pendiente',
        ]);
    }

    public function enviarMensaje($hiloId, $remitenteId, $cuerpo, $archivoImagen = null)
    {
        $rutaImagen = null;

        if ($archivoImagen) {
            $rutaImagen = $archivoImagen->store('chats', 'public');
        }

        return MensajeChat::create([
            'mch_id_hilo' => $hiloId,
            'mch_id_remitente' => $remitenteId,
            'mch_cuerpo' => $cuerpo,
            'mch_ruta_imagen' => $rutaImagen,
        ]);
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
                'hch_etiqueta_tema' => null
            ]);
        }

        return $hilo;
    }

    public function obtenerHistorialPaginado($usuario, $porPagina = 15)
    {
        $query = HiloChat::where('hch_estado', 'cerrado')->orderBy('updated_at', 'desc');

        if ($usuario->usu_es_admin) {
            return $query->with(['usuario', 'admin'])->paginate($porPagina);
        }

        return $query->with(['admin'])
                     ->where('hch_id_usuario', $usuario->usu_id)
                     ->paginate($porPagina);
    }
}