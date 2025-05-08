<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'email' => 'required|string',
            'password' => 'required|string',
            'remember' => 'boolean',
            'device_name' => 'nullable|string'
        ];
    }

    /**
     * Translate fields with user friendly name.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'email'    => __('auth.login.props.email'),
            'password' => __('auth.login.props.password'),
            'remember' => __('auth.login.props.remember'),
            'device_name' => __('auth.login.props.device_name')
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'email.required' => __('auth.login.validation.email_required'),
            'password.required' => __('auth.login.validation.password_required'),
        ];
    }
}
