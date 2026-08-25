<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Requisito extends Model
{
    protected $table = 'requisitos';
    protected $primaryKey = 'req_id';
    public $timestamps = false;

    protected $fillable = [
        'req_nombre_requisito', 
        'req_descripcion', 
        'req_formato_esperado'
    ];
    // Un Requisito pertenece a muchos Tipos de Solicitudes (Muchos a Muchos)
    public function tiposSolicitud()
    {
        return $this->belongsToMany(TipoSolicitud::class, 'tipo_solicitud_requisitos', 'tsr_req_id', 'tsr_tsi_id')
                    ->withPivot('tsr_es_obligatorio'); // Traemos el campo extra de la tabla pivote
    }
}