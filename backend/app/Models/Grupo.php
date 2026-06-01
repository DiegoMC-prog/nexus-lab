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

    // Un grupo contiene a muchos usuarios estudiantes
    public function estudiantes()
    {
        return $this->belongsToMany(User::class, 'grupo_user')->withTimestamps();
    }

    public function horarios()
    {
        return $this->hasMany(Horario::class, 'grupo_id');
    }

    protected static function booted()
    {
        static::deleting(function ($grupo) {
            $grupo->horarios()->delete();
        });

        static::restoring(function ($grupo) {
            $grupo->horarios()->withTrashed()->restore();
        });
    }
}
