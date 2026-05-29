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
}
