<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable('nombre', 'slug', 'tipo', 'require_auth')]
class Comando extends Model
{
    use SoftDeletes;
}
