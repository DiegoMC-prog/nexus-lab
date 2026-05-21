<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable('semestre_academico_id', 'carrera_id', 'codigo', 'nombre', 'creditos')]
class Materia extends Model
{
    use SoftDeletes;

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'carrera_id');
    }

    public function semestreAcademico()
    {
        return $this->belongsTo(SemestreAcademico::class, 'semestre_academico_id');
    }

    public function grupos()
    {
        return $this->hasMany(Grupo::class, 'materia_id');
    }

    public function horarios()
    {
        return $this->hasMany(Horario::class, 'materia_id');
    }
}
