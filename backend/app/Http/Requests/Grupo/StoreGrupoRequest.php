<?php

namespace App\Http\Requests\Grupo;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class StoreGrupoRequest extends FormRequest
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
            'materia_id' => [
                'required',
                'integer',
                // Valida que exista en la tabla materias y que no esté borrado por softdelete
                Rule::exists('materias', 'id')->whereNull('deleted_at'),
            ],
            'nombre' => [
                'required',
                'string',
                'max:100',
                // REGLA CLAVE: Nombre único combinado con materia_id y gestion, ignorando eliminados lógicos
                Rule::unique('grupos', 'nombre')
                    ->where('materia_id', $this->materia_id)
                    ->where('gestion', $this->gestion)
                    ->whereNull('deleted_at'),
            ],
            'gestion' => [
                'required',
                'string',
                'max:20', // Ej: "1/2026", "2/2026"
            ],
            'cupo_maximo' => [
                'nullable',
                'integer',
                'min:1', // Evita registros con 0 o cupos negativos
            ],
        ];
    }

    #[Override]
    public function messages()
    {
        return [
            'materia_id.exists' => 'La materia seleccionada no es válida o fue dada de baja.',
            'nombre.unique' => 'Este nombre de grupo ya se encuentra registrado para la materia y gestión seleccionadas.',
            'cupo_maximo.min' => 'El cupo mínimo permitido debe ser de al menos 1 estudiante.',
        ];
    }
}
