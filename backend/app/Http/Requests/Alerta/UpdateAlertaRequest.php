<?php

namespace App\Http\Requests\Alerta;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAlertaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estacion_id' => 'sometimes|required|integer|exists:estaciones,id',
            'config_alerta_id' => 'sometimes|required|integer|exists:config_alertas,id',
            'valor_actual' => 'sometimes|required|numeric',
            'estado' => 'sometimes|required|string|max:100',
            'resuelto_at' => 'nullable|date',
        ];
    }
}
