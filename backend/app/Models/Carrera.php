<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable('nombre', 'codigo')]
class Carrera extends Model
{
    use SoftDeletes;

    public function materias()
    {
        return $this->hasMany(Materia::class, 'carrera_id');
    }

    protected static function booted()
    {
        static::deleting(function ($carrera) {
            $carrera->materias()->delete();
        });

        static::restoring(function ($carrera) {
            $carrera->materias()->withTrashed()->restore();
        });
    }
}
