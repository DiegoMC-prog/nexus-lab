<?php

namespace App\Http\Requests\LogsComando;

use Illuminate\Foundation\Http\FormRequest;

class StoreLogsComandoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'usuario_id' => 'required|integer|exists:users,id',
            'estacion_id' => 'required|integer|exists:estaciones,id',
            'comando_id' => 'required|integer|exists:comandos,id',
            'origen' => 'required|string|max:255',
            'estado' => 'required|string|max:255',
            'mensaje_respuesta' => 'nullable|string',
        ];
    }
}
