<?php

namespace App\Models\ChatSoporte;

use Illuminate\Database\Eloquent\Model;

class MensajeChat extends Model
{
    protected $table = 'mensajes_chat';
    protected $primaryKey = 'mch_id';

    protected $fillable = [
        'mch_id_hilo', 'mch_id_remitente', 'mch_cuerpo', 'mch_ruta_imagen'
    ];
    public function hilo() {
        return $this->belongsTo(HiloChat::class, 'mch_id_hilo', 'hch_id');
    }

    public function remitente() {
        return $this->belongsTo(Usuario::class, 'mch_id_remitente', 'usu_id');
    }
}