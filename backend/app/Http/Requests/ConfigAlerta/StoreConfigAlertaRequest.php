<?php

namespace App\Http\Requests\ConfigAlerta;

use Illuminate\Foundation\Http\FormRequest;

class StoreConfigAlertaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'metrica' => 'required|string|max:255',
            'operador' => 'required|string|max:50',
            'valor_umbral' => 'required|numeric',
            'severidad' => 'required|string|max:100',
            'activo' => 'required|boolean',
        ];
    }
}
