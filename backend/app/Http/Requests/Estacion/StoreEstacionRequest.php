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
            'hostname' => 'required|string|min:3|max:100',
            'direccion_mac' => 'required|string' . ($estacionId ? '' : '|unique:estaciones,direccion_mac'),
            'direccion_ip' => 'required|ip|max:20' . ($estacionId ? '' : '|unique:estaciones,direccion_ip'),
            'so_info' => 'required|string',
            'estado' => 'required|string',
            'version_agente' => 'required|string',
        ];
    }
}
