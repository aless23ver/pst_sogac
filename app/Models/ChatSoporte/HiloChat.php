<?php

namespace App\Models\ChatSoporte;

use Illuminate\Database\Eloquent\Model;
use App\Models\Usuario;

class HiloChat extends Model
{
    protected $table = 'hilos_chat';
    protected $primaryKey = 'hch_id';

    protected $fillable = [
        'hch_id_usuario', 'hch_id_admin', 'hch_estado', 'hch_etiqueta_tema', 'hch_fecha_solicitud_cierre'
    ];
    
    public function usuario() {
        return $this->belongsTo(Usuario::class, 'hch_id_usuario', 'usu_id');
    }

    public function admin() {
        return $this->belongsTo(Usuario::class, 'hch_id_admin', 'usu_id');
    }

    public function mensajes() {
        return $this->hasMany(MensajeChat::class, 'mch_id_hilo', 'hch_id');
    }
}