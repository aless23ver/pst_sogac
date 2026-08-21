<?php

namespace App\Models\ChatSoporte;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $table = 'usuarios';
    protected $primaryKey = 'usu_id';

    protected $fillable = [
        'usu_nombre', 'usu_correo', 'usu_clave', 'usu_es_admin'
    ];

    public function hilosComoUsuario() {
        return $this->hasMany(HiloChat::class, 'hch_id_usuario', 'usu_id');
    }

    public function hilosComoAdmin() {
        return $this->hasMany(HiloChat::class, 'hch_id_admin', 'usu_id');
    }
}