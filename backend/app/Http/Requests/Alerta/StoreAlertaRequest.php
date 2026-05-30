<?php

namespace App\Http\Requests\Alerta;

use Illuminate\Foundation\Http\FormRequest;

class StoreAlertaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estacion_id' => 'required|integer|exists:estaciones,id',
            'config_alerta_id' => 'required|integer|exists:config_alertas,id',
            'valor_actual' => 'required|numeric',
            'estado' => 'required|string|max:100',
            'resuelto_at' => 'nullable|date',
        ];
    }
}
