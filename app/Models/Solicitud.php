<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    protected $table = 'solicitudes';
    
    // Permitimos actualización masiva de este campo
    protected $fillable = ['estado']; 

    // Aquí reemplazamos tu JOIN de SQL
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}