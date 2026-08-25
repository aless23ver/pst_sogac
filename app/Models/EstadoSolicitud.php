<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoSolicitud extends Model
{
    protected $table = 'estado_solicitudes';
    protected $primaryKey = 'eso_id';
    public $timestamps = false;

    protected $fillable = [
        'eso_nombre_estado', 
        'eso_descripcion'
    ];
    // Un Estado puede estar en muchas Solicitudes
    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'sol_eso_id', 'eso_id');
    }
    // Un estado puede aparecer en el historial como el estado "viejo"
    public function historialesComoAnterior()
    {
        return $this->hasMany(HistorialEstadoSolicitud::class, 'hes_eso_id_anterior', 'eso_id');
    }

    // Un estado puede aparecer en el historial como el estado "nuevo" al que se pasó
    public function historialesComoNuevo()
    {
        return $this->hasMany(HistorialEstadoSolicitud::class, 'hes_eso_id_nuevo', 'eso_id');
    }
}