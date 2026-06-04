<?php

namespace App\Http\Requests\Estacion;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEstacionRequest extends FormRequest
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
        $estacionId = null;
        if ($this->has('uuid')) {
            $estacion = \App\Models\Estacion::where('uuid', $this->uuid)->first();
            if ($estacion) {
                $estacionId = $estacion->id;
            }
        }

        return [
            'laboratorio_id' => 'nullable|integer|exists:laboratorios,id',
            'estudiante_actual_id' => 'nullable|integer|exists:users,id',
            'uuid' => 'required|uuid' . ($estacionId ? '' : '|unique:estaciones,uuid'),
            'hostname' => 'required|string|max:100',
            'direccion_mac' => [
                'required',
                'string',
                'regex:/^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$/',
                $estacionId ? '' : 'unique:estaciones,direccion_mac'
            ],
            'direccion_ip' => 'required|ip|max:20' . ($estacionId ? '' : '|unique:estaciones,direccion_ip'),
            'so_info' => 'required|string',
            'estado' => 'required|string',
            'version_agente' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'direccion_mac.regex' => 'El formato de la dirección MAC no es válido. Use el formato XX:XX:XX:XX:XX:XX o XX-XX-XX-XX-XX-XX.',
            'direccion_mac.unique' => 'Esta dirección MAC ya está registrada en el sistema.',
            'direccion_ip.unique' => 'Esta dirección IP ya está registrada en el sistema.',
            'uuid.unique' => 'Esta estación ya está registrada en el sistema.',
        ];
    }
}
