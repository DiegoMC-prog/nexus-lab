<?php

namespace App\Http\Requests\Curso;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCursoRequest extends FormRequest
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
        $cursoId = $this->route('curso')?->id ?? $this->route('curso');
        $carreraId = $this->carrera_id;

        return [
            'carrera_id' => 'required|exists:carreras,id',

            'semestre_academico_id' => [
                'required',
                'exists:semestres_academicos,id',

                Rule::unique('cursos')
                    ->ignore($cursoId)
                    ->where(function ($query) use ($carreraId) {
                        return $query->where('carrera_id', $carreraId)
                            ->whereNull('deleted_at');
                    })
            ],
        ];
    }
}
