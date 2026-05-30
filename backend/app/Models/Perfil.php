<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('perfil')]
#[Fillable('user_id', 'telefono', 'departamento')]
class Perfil extends Model
{
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
