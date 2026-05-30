<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('logs_telemetria')]
#[Fillable('estacion_id','carga_cpu', 'uso_ram_mb', 'temp_cpu', 'uso_disco', 'latencia_red')]
class LogsTelemetria extends Model
{
    use SoftDeletes;

    public function estacion()
    {
        return $this->belongsTo(Estacion::class);
    }
}
