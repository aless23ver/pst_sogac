<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreguntasFrecuentes extends Model
{
    protected $fillable = ['pregunta','respuesta'];
    public const PAGINATE = 10;
}
