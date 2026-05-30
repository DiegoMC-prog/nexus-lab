<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class UpdateUserRequest extends FormRequest
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
        $userId = $this->route('user')?->id ?? $this->route('user');

        return [
            'name' => 'required|string|max:200|unique:users,name,' . $userId,
            'email' => 'required|email|string|max:255|unique:users,email,' . $userId,
            'role' => 'required|integer|exists:roles,id',
            'estado' => 'required|string',
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            // Mensajes para el campo 'name'
            'name.required' => 'El nombre de usuario es obligatorio.',
            'name.string' => 'El nombre debe ser una cadena de texto válida.',
            'name.max' => 'El nombre no puede superar los 200 caracteres.',
            'name.unique' => 'Este nombre de usuario ya se encuentra registrado.',

            // Mensajes para el campo 'email'
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ingresar una dirección de correo válida.',
            'email.string' => 'El correo electrónico debe ser una cadena de texto.',
            'email.max' => 'El correo electrónico no puede superar los 255 caracteres.',
            'email.unique' => 'Este correo electrónico ya está en uso por otro usuario.',

            // Mensajes para el campo 'role'
            'role.required' => 'Debe seleccionar un rol para el usuario.',
            'role.integer' => 'El formato del rol seleccionado no es válido.',
            'role.exists' => 'El rol seleccionado no existe en el sistema.',

            // Mensajes para el campo 'estado'
            'estado.required' => 'El estado del usuario es obligatorio.',
            'estado.string' => 'El estado debe ser una cadena de texto válida.',
        ];
    }
}
