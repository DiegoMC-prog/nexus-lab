<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable('usuario_id', 'estacion_id', 'comando_id', 'origen', 'estado', 'mensaje_respuesta')]
class LogsComando extends Model
{
    use SoftDeletes;

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function estacion()
    {
        return $this->belongsTo(Estacion::class, 'estacion_id');
    }

    public function comando()
    {
        return $this->belongsTo(Comando::class, 'comando_id');
    }
}
