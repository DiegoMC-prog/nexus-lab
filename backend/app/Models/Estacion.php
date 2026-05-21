<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('estaciones')]
#[Fillable(
    'laboratorio_id',
    'estudiante_actual_id',
    'hostname',
    'direccion_mac',
    'direccion_ip',
    'so_info',
    'estado',
    'version_agente',
    'ultima_conexion'
)]
class Estacion extends Model
{
    use SoftDeletes;

    public function laboratorio()
    {
        return $this->belongsTo(Laboratorio::class, 'laboratorio_id');
    }

    public function estudianteActual()
    {
        return $this->belongsTo(User::class, 'estudiante_actual_id');
    }

    public function Hardware()
    {
        return $this->hasOne(PerfilHardware::class, 'estacion_id');
    }

    public function telemetrias()
    {
        return $this->hasMany(LogsTelemetria::class, 'estacion_id');
    }

    public function ultimaTelemetria()
    {
        return $this->hasOne(LogsTelemetria::class, 'estacion_id')->latestOfMany();
    }

    public function alertas()
    {
        return $this->hasMany(Alerta::class, 'estacion_id');
    }

    public function logsComandos()
    {
        return $this->hasMany(LogsComando::class, 'estacion_id');
    }
}
