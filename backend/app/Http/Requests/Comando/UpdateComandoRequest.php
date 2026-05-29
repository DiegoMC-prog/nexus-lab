<?php

namespace App\Http\Requests\Comando;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateComandoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $comando = $this->route('comando');
        $id = is_object($comando) ? $comando->id : $comando;

        return [
            'nombre' => 'sometimes|required|string|max:255',
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('comandos', 'slug')->ignore($id)->whereNull('deleted_at'),
            ],
            'tipo' => 'sometimes|required|string|max:100',
            'require_auth' => 'sometimes|required|boolean',
        ];
    }
}
