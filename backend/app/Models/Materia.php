<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable('curso_id', 'codigo', 'nombre', 'creditos')]
class Materia extends Model
{
    use SoftDeletes;

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'curso_id');
    }

    public function grupos()
    {
        return $this->hasMany(Grupo::class, 'materia_id');
    }
}
