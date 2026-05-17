<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable('laboratorio_id', 'docente_id', 'materia', 'fecha', 'hora_inicio', 'hora_fin')]
class Horario extends Model
{
    
}
