<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable('nombre', 'codigo')]
class Carrera extends Model
{
    use SoftDeletes;

    public function cursos()
    {
        return $this->hasMany(Curso::class, 'carrera_id');
    }
}
