<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('biometria_vocal')]
#[Fillable(['usuario_id', 'firma_mcc', 'frecuencia_muestreo', 'frase_registro', 'umbral_confianza'])]
class BiometriaVocal extends Model
{
    use SoftDeletes;

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
