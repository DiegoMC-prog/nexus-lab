<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable('id', 'nombre', 'pabellon', 'piso', 'activo')]
class Laboratorio extends Model
{
    use SoftDeletes;

    public function estaciones()
    {
        return $this->hasMany(Estacion::class, 'laboratorio_id');
    }

    public function horarios()
    {
        return $this->hasMany(Horario::class, 'laboratorio_id');
    }
}
