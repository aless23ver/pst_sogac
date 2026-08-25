<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    protected $table = 'solicitudes';
    protected $primaryKey = 'sol_id';
    public $timestamps = false;

    protected $fillable = [
        'sol_usu_id', 
        'sol_tsi_id', 
        'sol_lac_id', 
        'sol_eso_id', 
        'sol_id_seguimiento', 
        'sol_motivo_detallado', 
        'sol_prioridad', 
        'sol_fecha_creacion', 
        'sol_fecha_ultima_actualizacion', 
        'sol_fecha_resolucion'
    ];
    // Relaciones hacia arriba (Pertenece a...)
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'sol_usu_id', 'usu_id');
    }

    public function tipoSolicitud()
    {
        return $this->belongsTo(TipoSolicitud::class, 'sol_tsi_id', 'tsi_id');
    }

    public function lapsoAcademico()
    {
        return $this->belongsTo(LapsoAcademico::class, 'sol_lac_id', 'lac_id');
    }

    public function estadoActual()
    {
        return $this->belongsTo(EstadoSolicitud::class, 'sol_eso_id', 'eso_id');
    }

    // Relaciones hacia abajo (Tiene muchos...)
    public function historialEstados()
    {
        return $this->hasMany(HistorialEstadoSolicitud::class, 'hes_sol_id', 'sol_id');
    }

    public function documentaciones()
    {
        return $this->hasMany(Documentacion::class, 'doc_sol_id', 'sol_id');
    }
}