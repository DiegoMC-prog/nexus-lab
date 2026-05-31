<?php

namespace App\Http\Requests\Perfil;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $perfil = $this->route('perfil');
        $id = is_object($perfil) ? $perfil->id : $perfil;

        return [
            'user_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:users,id',
                Rule::unique('perfil', 'user_id')->ignore($id),
            ],
            'telefono' => 'sometimes|required|string|max:50',
        ];
    }
}
