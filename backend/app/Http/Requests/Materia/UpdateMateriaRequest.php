<?php

namespace App\Http\Requests\Materia;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMateriaRequest extends FormRequest
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
        $id = $this->route('materia')->id;

        return [
            'semestre_academico_id' => 'required|integer|exists:semestres_academicos,id',
            'carrera_id' => 'required|integer|exists:carreras,id',
            'codigo' => ['required', 'string', 'regex:/^[a-zA-Z0-9\-]+$/', 'unique:materias,codigo,' . $id],
            'nombre' => 'required|string|min:3|unique:materias,nombre,' . $id,
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
        $materia = $this->route('materia');

        $validator->after(function ($validator) use ($materia) {
            // Check original semester
            if ($materia && $materia->semestreAcademico && $materia->semestreAcademico->isClosed()) {
                $validator->errors()->add('semestre_academico_id', 'No se puede editar una materia que pertenece a un semestre cerrado.');
            }

            // Check new semester (if changing)
            $newSemestreId = $this->input('semestre_academico_id');
            if ($newSemestreId && (!$materia || $newSemestreId != $materia->semestre_academico_id)) {
                $newSemestre = \App\Models\SemestreAcademico::find($newSemestreId);
                if ($newSemestre && $newSemestre->isClosed()) {
                    $validator->errors()->add('semestre_academico_id', 'No se puede mover la materia a un semestre cerrado.');
                }
            }
        });
    }
}
