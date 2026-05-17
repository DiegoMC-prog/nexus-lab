<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable('user_id', 'nombre_dispositivo', 'fingerprint')]
class DispositivoConocido extends Model
{
    use SoftDeletes;

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
