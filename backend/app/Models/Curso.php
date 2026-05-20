<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable('carrera_id', 'nombre', 'turno')]
class Curso extends Model
{
    use SoftDeletes;

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'carrera_id');
    }

    public function materias()
    {
        return $this->hasMany(Materia::class, 'curso_id');
    }
}
