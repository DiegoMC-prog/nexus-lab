<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable('estacion_id', 'config_alerta_id', 'valor_actual', 'estado', 'resuelto_at')]
class Alerta extends Model
{
    use SoftDeletes;

    public function estacion()
    {
        return $this->belongsTo(Estacion::class, 'estacion_id');
    }

    public function configuracionAlerta()
    {
        return $this->belongsTo(ConfigAlerta::class, 'config_alerta_id');
    }
}
