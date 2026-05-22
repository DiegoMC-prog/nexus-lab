<?php

namespace App\Http\Requests\Materia;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMateriaRequest extends FormRequest
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
            'semestre_academico_id' => 'required|integer|exists:semestres_academicos,id',
            'carrera_id' => 'required|integer|exists:carreras,id',
            'codigo' => 'required|string|unique:materias,codigo',
            'nombre' => 'required|string|min:3',
            'creditos' => 'nullable|integer',
        ];
    }
}
