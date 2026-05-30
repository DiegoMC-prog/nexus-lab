<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('semestres_academicos')]
#[Fillable('nombre')]
class SemestreAcademico extends Model
{
    use SoftDeletes;

    public function materias()
    {
        return $this->hasMany(Materia::class, 'semestre_academico_id');
    }
}
