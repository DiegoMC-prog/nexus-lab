<?php

namespace App\Http\Requests\Curso;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class StoreCursoRequest extends FormRequest
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
        $carreraId = $this->carrera_id;

        return [
            'carrera_id' => 'required|exists:carreras,id',

            'semestre_academico_id' => [
                'required',
                'exists:semestres_academicos,id',

                Rule::unique('cursos')->where(function ($query) use ($carreraId) {
                    return $query->where('carrera_id', $carreraId)
                        ->whereNull('deleted_at'); 
                })
            ],
        ];
    }

    #[Override]
    public function messages()
    {
        return [
            'semestre_academico_id.unique' => 'Este semestre ya se encuentra asignado a la carrera seleccionada.'
        ];
    }
}
