<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('config_alertas')]
#[Fillable('metrica', 'operador', 'valor_umbral', 'severidad', 'activo')]
class ConfigAlerta extends Model
{
    use SoftDeletes;

    public function alertas()
    {
        return $this->hasMany(Alerta::class, 'config_alerta_id');
    }
}
