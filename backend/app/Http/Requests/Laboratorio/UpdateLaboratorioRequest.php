<?php

namespace App\Http\Requests\Laboratorio;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class UpdateLaboratorioRequest extends FormRequest
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
            'nombre' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('laboratorios', 'nombre')
                    ->ignore($this->route('laboratorio'))
                    ->whereNull('deleted_at'),
            ],
            'pabellon' => 'sometimes|string|max:255',
            'piso' => 'sometimes|string|max:100',
            'activo' => 'sometimes|boolean',
            'capacidad' => 'sometimes|integer|min:1|max:500',
        ];
    }

    public function messages()
    {
        return [
            'nombre.unique' => 'El nombre ya está registrado.',
            'activo.boolean' => 'El campo activo debe ser verdadero o falso.',
            'capacidad.integer' => 'La capacidad debe ser un número entero.',
            'capacidad.min' => 'La capacidad mínima es 1 puesto.',
            'capacidad.max' => 'La capacidad máxima es 500 puestos.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $laboratorio = is_object($this->route('laboratorio')) ? $this->route('laboratorio') : \App\Models\Laboratorio::find($this->route('laboratorio'));
            if (!$laboratorio) return;

            // If we are deactivating
            if ($this->has('activo') && !$this->input('activo') && $laboratorio->activo) {
                if ($laboratorio->horarios()->exists()) {
                    $validator->errors()->add('activo', 'No se puede desactivar el laboratorio porque tiene horarios de materias asignados.');
                }
            }

            // If reducing capacity below current workstation count
            if ($this->has('capacidad')) {
                $nuevaCapacidad = (int) $this->input('capacidad');
                $estacionesActuales = $laboratorio->estaciones()->count();
                if ($nuevaCapacidad < $estacionesActuales) {
                    $validator->errors()->add('capacidad', "No se puede reducir la capacidad a {$nuevaCapacidad} porque el laboratorio ya tiene {$estacionesActuales} estaciones registradas.");
                }
            }
        });
    }
}
