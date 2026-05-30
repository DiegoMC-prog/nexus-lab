<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('logs_aplicaciones_prohibidas')]
#[Fillable('estacion_id', 'usuario_id', 'nombre_proceso', 'ruta_ejecutable', 'accion_tomada')]
class LogAplicacionProhibida extends Model
{
    /**
     * Desactivar el campo 'updated_at', ya que solo nos interesa la fecha de creación.
     */
    public const UPDATED_AT = null;

    /**
     * Relación con la Estación.
     */
    public function estacion()
    {
        return $this->belongsTo(Estacion::class, 'estacion_id');
    }

    /**
     * Relación con el Usuario.
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
