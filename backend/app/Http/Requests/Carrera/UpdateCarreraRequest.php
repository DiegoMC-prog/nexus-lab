<?php

namespace App\Http\Requests\Carrera;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCarreraRequest extends FormRequest
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
        $id = $this->route('carrera')->id;
        return [
            'nombre' => 'required|string|min:3|unique:carreras,nombre,' . $id,
            'codigo' => 'required|string|min:3|unique:carreras,codigo,' . $id,
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.unique' => 'El código de carrera ya pertenece a otra facultad.',
            'nombre.unique' => 'Ya existe una carrera con ese nombre.',
        ];
    }
}
