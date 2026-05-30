<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable('carrera_id', 'semestre_academico_id')]
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

    public function semestreAcademico(): BelongsTo
    {
        return $this->belongsTo(SemestreAcademico::class, 'semestre_academico_id');
    }
}
