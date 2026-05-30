<?php

namespace App\Http\Requests\ConfigAlerta;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfigAlertaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'metrica' => 'sometimes|required|string|max:255',
            'operador' => 'sometimes|required|string|max:50',
            'valor_umbral' => 'sometimes|required|numeric',
            'severidad' => 'sometimes|required|string|max:100',
            'activo' => 'sometimes|required|boolean',
        ];
    }
}
