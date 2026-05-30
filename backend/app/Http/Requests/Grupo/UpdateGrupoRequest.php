<?php

namespace App\Http\Requests\Grupo;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class UpdateGrupoRequest extends FormRequest
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
        // Capturamos el ID del grupo que viaja por la URL de la ruta de la API (ej: /api/grupos/{grupo})
        $grupoId = $this->route('grupo');

        // Si pasaste el objeto modelo directamente en la ruta por Route Model Binding, usa: $grupoId = $this->route('grupo')->id;

        return [
            'materia_id' => [
                'required',
                'integer',
                Rule::exists('materias', 'id')->whereNull('deleted_at'),
            ],
            'nombre' => [
                'required',
                'string',
                'max:100',
                // REGLA CLAVE OPTIMIZADA: Valida la combinación única pero ignora este registro en particular
                Rule::unique('grupos', 'nombre')
                    ->where('materia_id', $this->materia_id)
                    ->where('gestion', $this->gestion)
                    ->whereNull('deleted_at')
                    ->ignore($grupoId),
            ],
            'gestion' => [
                'required',
                'string',
                'max:20',
            ],
            'cupo_maximo' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ];
    }

    #[Override]
    public function messages()
    {
        return [
            'materia_id.exists' => 'La materia seleccionada no es válida o fue dada de baja.',
            'nombre.unique' => 'Este nombre de grupo ya se encuentra en uso por otra sección de esta materia en la misma gestión.',
            'cupo_maximo.min' => 'El cupo mínimo permitido debe ser de al menos 1 estudiante.',
        ];
    }
}
