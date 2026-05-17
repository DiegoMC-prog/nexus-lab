<?php

namespace App\Http\Requests\Laboratorio;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class UpdateLaboratorioRequest extends FormRequest
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
            'nombre' => 'sometimes|string|max:255',
            'pabellon' => 'sometimes|string|max:255',
            'piso' => 'sometimes|string|max:100',
            'activo' => 'sometimes|boolean',
        ];
    }

    #[Override]
    public function messages()
    {
        return [
            'activo.boolean' => 'El campo activo debe ser verdadero o falso.',
        ];
    }
}
