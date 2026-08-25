<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoSolicitud extends Model
{
    protected $table = 'tipo_solicitudes';
    protected $primaryKey = 'tsi_id';
    public $timestamps = false;

    protected $fillable = [
        'tsi_nombre_tipo', 
        'tsi_descripcion', 
        'tsi_tiempo_estimado_dias', 
        'tsi_requiere_aprobacion_especial', 
        'tsi_estado_tipo'
    ];
    // Un Tipo de Solicitud tiene muchos Requisitos (Muchos a Muchos)
    public function requisitos()
    {
        return $this->belongsToMany(Requisito::class, 'tipo_solicitud_requisitos', 'tsr_tsi_id', 'tsr_req_id')
                    ->withPivot('tsr_es_obligatorio');
    }

    // Un Tipo de Solicitud tiene muchas Solicitudes hechas por los usuarios
    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'sol_tsi_id', 'tsi_id');
    }
}