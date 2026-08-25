<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Documentacion extends Model
{
    protected $table = 'documentaciones';
    protected $primaryKey = 'doc_id';
    public $timestamps = false;

    protected $fillable = [
        'doc_sol_id', 
        'doc_nombre_original_archivo', 
        'doc_tipo_documento', 
        'doc_formato_archivo', 
        'doc_tamano_bytes', 
        'doc_ruta_almacenamiento_url', 
        'doc_fecha_subida', 
        'doc_estado_validacion'
    ];
    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'doc_sol_id', 'sol_id');
    }
}