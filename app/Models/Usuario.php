<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $table = 'usuarios';
    protected $primaryKey = 'usu_id';
    public $timestamps = false;

    protected $fillable = [
        'usu_rol', 
        'usu_tdo_id', 
        'usu_primer_nombre', 
        'usu_segundo_nombre', 
        'usu_primer_apellido', 
        'usu_segundo_apellido', 
        'usu_numero_documento', 
        'usu_correo_electronico', 
        'usu_numero_telefono', 
        'usu_contrasena_hash', 
        'usu_estado_cuenta', 
        'usu_fecha_registro', 
        'usu_ultimo_acceso'
    ];
    
    public function hilosComoUsuario() {
        return $this->hasMany(HiloChat::class, 'hch_id_usuario', 'usu_id');
    }

    public function hilosComoAdmin() {
        return $this->hasMany(HiloChat::class, 'hch_id_admin', 'usu_id');
    }
    // Un Usuario pertenece a un Tipo de Documento
    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class, 'usu_tdo_id', 'tdo_id');
    }

    // Un Usuario (Estudiante) tiene muchas Solicitudes
    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'sol_usu_id', 'usu_id');
    }
    // Un Usuario (Admin) registra/modifica muchos historiales de estados
    public function historialesModificados()
    {
        return $this->hasMany(HistorialEstadoSolicitud::class, 'hes_usu_id_responsable', 'usu_id');
    }
}