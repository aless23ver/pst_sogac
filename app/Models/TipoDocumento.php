<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoDocumento extends Model
{
    protected $table = 'tipo_documentos';
    protected $primaryKey = 'tdo_id';
    public $timestamps = false;

    protected $fillable = [
        'tdo_nombre_documento', 
        'tdo_abreviatura'
    ];
    // Un Tipo de Documento tiene muchos Usuarios
    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'usu_tdo_id', 'tdo_id');
    }
}
