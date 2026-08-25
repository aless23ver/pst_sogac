<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LapsoAcademico extends Model
{
    protected $table = 'lapso_academicos';
    protected $primaryKey = 'lac_id';
    // Indicamos que el ID es un string porque usaste VARCHAR en tu SQL original
    protected $keyType = 'string'; 
    public $timestamps = false;

    protected $fillable = [
        'lac_id_lapso', 
        'lac_fecha_inicio', 
        'lac_fecha_cierre', 
        'lac_estado_lapso'
    ];
    // Un Lapso tiene muchas Solicitudes
    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'sol_lac_id', 'lac_id');
    }
}