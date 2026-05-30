<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class VerifyOtpRequest extends FormRequest
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
            'email' => 'required|email',
            'otp_code' => 'required|string|size:6',
            'fingerprint' => 'required|string',
        ];
    }

    #[Override]
    public function messages()
    {
        return [
            'otp_code.required' => 'El codigo otp no esta reconocido o se vencio',
            'fingerprint' => 'Falta nombre del identificador del dispositivo',
        ];
    }
}
