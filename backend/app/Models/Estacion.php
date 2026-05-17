<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('etaciones')]
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
        return $this->belongsTo(Laboratorio::class);
    }

    public function estudianteActual()
    {
        return $this->belongsTo(User::class);
    }

    public function Hardware()
    {
        return $this->hasOne(PerfilHardware::class, 'estacion_id');
    }

    public function ultimaTelemetria()
    {
        return $this->hasOne(LogsTelemetria::class)->latestOfMany();
    }

    public function alertas()
    {
        return $this->hasMany(Alerta::class, 'estacion_id');
    }

    
}
