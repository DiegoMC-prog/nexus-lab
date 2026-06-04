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
            'codigo' => ['required', 'string', 'regex:/^[a-zA-Z0-9\-]+$/', 'unique:materias,codigo'],
            'nombre' => 'required|string|min:3|unique:materias,nombre',
            'creditos' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.regex' => 'La sigla solo puede contener letras, números y guiones (sin espacios ni caracteres especiales).',
            'codigo.unique' => 'Ya existe una materia registrada con esta sigla.',
            'nombre.unique' => 'Ya existe una materia registrada con este nombre.',
            'creditos.min' => 'Los créditos no pueden ser un valor negativo.',
            'creditos.integer' => 'Los créditos deben ser un número entero.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $semestreId = $this->input('semestre_academico_id');
            if ($semestreId) {
                $semestre = \App\Models\SemestreAcademico::find($semestreId);
                if ($semestre && $semestre->isClosed()) {
                    $validator->errors()->add('semestre_academico_id', 'No se puede crear una materia en un semestre cerrado.');
                }
            }
        });
    }
}
