<?php

namespace App\Http\Requests\Laboratorio;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class StoreLaboratorioRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'pabellon' => 'required|string|max:255',
            'piso' => 'required|string|max:100',
            'activo' => 'required|boolean',
        ];
    }

    #[Override]
    public function messages()
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'pabellon.required' => 'El pabellón es obligatorio.',
            'piso.required' => 'El piso es obligatorio.',
            'activo.required' => 'El estado activo es obligatorio.',
            'activo.boolean' => 'El campo activo debe ser verdadero o falso.',
        ];
    }
}
