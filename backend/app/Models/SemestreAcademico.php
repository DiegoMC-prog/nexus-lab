<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('semestres_academicos')]
#[Fillable('nombre', 'fecha_inicio', 'fecha_fin', 'estado')]
class SemestreAcademico extends Model
{
    use SoftDeletes;

    public function isClosed(): bool
    {
        return $this->estado === 'cerrado';
    }

    public function materias()
    {
        return $this->hasMany(Materia::class, 'semestre_academico_id');
    }

    protected static function booted()
    {
        static::deleting(function ($semestre) {
            $semestre->materias()->delete();
        });

        static::restoring(function ($semestre) {
            $semestre->materias()->withTrashed()->restore();
        });
    }
}
