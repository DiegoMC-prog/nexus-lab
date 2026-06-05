<?php

namespace App\Http\Requests\SemestreAcademico;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSemestreRequest extends FormRequest
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
        $semestre = $this->route('semestre');

        return [
            'nombre' => 'required|string|min:3|unique:semestres_academicos,nombre,' . $semestre->id,
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',
        ];
    }

    public function withValidator($validator)
    {
        $semestre = $this->route('semestre');

        $validator->after(function ($validator) use ($semestre) {
            if ($semestre->isClosed()) {
                $validator->errors()->add('estado', 'No se puede editar un semestre que ya ha sido cerrado.');
            }
        });
    }
}
