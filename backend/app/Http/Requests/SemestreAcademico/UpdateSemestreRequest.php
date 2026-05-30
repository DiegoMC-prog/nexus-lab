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
        $id = $this->route('semestre')->id;

        return [
            'nombre' => 'required|string|min:3|unique:semestres_academicos,nombre,' . $id,
        ];
    }
}
