<?php

namespace App\Http\Requests\LogsComando;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLogsComandoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'usuario_id' => 'sometimes|required|integer|exists:users,id',
            'estacion_id' => 'sometimes|required|integer|exists:estaciones,id',
            'comando_id' => 'sometimes|required|integer|exists:comandos,id',
            'origen' => 'sometimes|required|string|max:255',
            'estado' => 'sometimes|required|string|max:255',
            'mensaje_respuesta' => 'nullable|string',
        ];
    }
}
