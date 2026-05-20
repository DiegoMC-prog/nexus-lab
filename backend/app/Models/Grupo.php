<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable('materia_id', 'nombre', 'gestion', 'cupo_maximo')]
class Grupo extends Model
{
    use SoftDeletes;

    public function materia()
    {
        return $this->belongsTo(Materia::class, 'materia_id');
    }
}
