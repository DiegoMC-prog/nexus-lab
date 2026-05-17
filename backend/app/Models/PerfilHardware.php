<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('perfil_hardware')]
#[Fillable('estacion_id', 'cpu_modelo', 'ram_total_gb', 'cantidad_almacenamiento', 'gpu_modelo',)]
class PerfilHardware extends Model
{
    use SoftDeletes;

    public function estacion()
    {
        return $this->belongsTo(Estacion::class);
    }
}
