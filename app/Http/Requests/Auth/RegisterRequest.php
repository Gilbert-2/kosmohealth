<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'name'                  => 'required|string|min:2|max:100',
            'email'                 => 'required|email:rfc,dns|unique:users,email',
            'username'              => 'required|string|min:3|max:50|unique:users,username|alpha_dash',
            'mobile'                => 'nullable|string|unique:users,mobile',
            'password'              => [
                'required',
                'string',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
                'confirmed'
            ],
            'password_confirmation' => 'required|string'
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
            'name'                  => __('auth.register.props.name'),
            'email'                 => __('auth.register.props.email'),
            'mobile'                => __('auth.register.props.mobile'),
            'username'              => __('auth.register.props.username'),
            'password'              => __('auth.register.props.password'),
            'password_confirmation' => __('auth.register.props.password_confirmation')
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
            'name.required'                  => __('auth.register.validation.name_required'),
            'email.required'                 => __('auth.register.validation.email_required'),
            'email.email'                    => __('auth.register.validation.email_invalid'),
            'email.unique'                   => __('auth.register.validation.email_unique'),
            'username.required'              => __('auth.register.validation.username_required'),
            'username.unique'                => __('auth.register.validation.username_unique'),
            'password.required'              => __('auth.register.validation.password_required'),
            'password.min'                   => __('auth.register.validation.password_min'),
            'password_confirmation.required' => __('auth.register.validation.password_confirmation_required'),
            'password_confirmation.same'     => __('auth.register.validation.password_confirmation_same'),
        ];
    }
}
