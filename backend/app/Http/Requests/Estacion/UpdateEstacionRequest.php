<?php

namespace App\Http\Requests\Estacion;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEstacionRequest extends FormRequest
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
        $estacionId = $this->route('estacion');

        return [
            'laboratorio_id' => [
                'required',
                'integer',
                'exists:laboratorios,id',
            ],

            'estudiante_actual_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'uuid' => [
                'required',
                'uuid',
                Rule::unique('estaciones', 'uuid')
                    ->ignore($estacionId)
                    ->whereNull('deleted_at'),
            ],

            'hostname' => [
                'required',
                'string',
                'min:3',
                'max:100',
            ],

            'direccion_mac' => [
                'required',
                'string',
                'regex:/^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$/',
                Rule::unique('estaciones', 'direccion_mac')
                    ->ignore($estacionId)
                    ->whereNull('deleted_at'),
            ],

            'direccion_ip' => [
                'required',
                'ip',
                Rule::unique('estaciones', 'direccion_ip')
                    ->ignore($estacionId)
                    ->whereNull('deleted_at'),
            ],

            'so_info' => [
                'required',
                'string',
                'max:255',
            ],

            'estado' => [
                'required',
                Rule::in([
                    'activa',
                    'inactiva',
                    'mantenimiento',
                    'desconectada',
                ]),
            ],

            'version_agente' => [
                'required',
                'string',
                'max:50',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'direccion_mac.regex' => 'El formato de la dirección MAC no es válido. Use el formato XX:XX:XX:XX:XX:XX o XX-XX-XX-XX-XX-XX.',
            'direccion_mac.unique' => 'Esta dirección MAC ya está registrada en otro equipo.',
            'direccion_ip.unique' => 'Esta dirección IP ya está registrada en otro equipo.',
            'uuid.unique' => 'El UUID ya pertenece a otra estación registrada.',
            'estado.in' => 'El estado indicado no es válido.',
        ];
    }
}
