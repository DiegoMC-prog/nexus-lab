<?php

namespace App\Http\Requests\Comando;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComandoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('comandos', 'slug')->whereNull('deleted_at'),
            ],
            'tipo' => 'required|string|max:100',
            'require_auth' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'slug.required' => 'El slug es obligatorio.',
            'slug.unique' => 'El slug ya está registrado.',
            'tipo.required' => 'El tipo es obligatorio.',
            'require_auth.required' => 'El requerimiento de autenticación es obligatorio.',
            'require_auth.boolean' => 'El campo require_auth debe ser verdadero o falso.',
        ];
    }
}
