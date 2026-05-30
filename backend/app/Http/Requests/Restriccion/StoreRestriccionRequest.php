<?php

namespace App\Http\Requests\Restriccion;

use Illuminate\Foundation\Http\FormRequest;

class StoreRestriccionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'laboratorio_id' => 'nullable|exists:laboratorios,id',
            'nombre_aplicacion' => 'required|string|max:255',
            'nombre_proceso' => 'required|string|max:255',
            'tipo_restriccion' => 'nullable|string|max:255',
            'activo' => 'nullable|boolean',
        ];
    }
}
