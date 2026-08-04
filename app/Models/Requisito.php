<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Requisito extends Model
{
    protected $table = 'requisitos';
    
    // Los campos que permitimos llenar desde el formulario
    protected $fillable = ['nombre_tramite', 'documentos_necesarios'];

    // NOTA: Si tu tabla original no tiene las columnas 'created_at' y 'updated_at', 
    // debes descomentar la siguiente línea para que Laravel no intente buscarlas:
    // public $timestamps = false;
}