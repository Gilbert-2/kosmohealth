<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $user = auth()->user();
        
        $rules = [
            'first_name' => 'nullable|string|min:2|max:50',
            'last_name' => 'nullable|string|min:2|max:50',
            'email' => [
                'nullable',
                'email:rfc,dns',
                Rule::unique('users')->ignore($user->id)
            ],
            'username' => [
                'nullable',
                'string',
                'min:3',
                'max:50',
                'alpha_dash',
                Rule::unique('users')->ignore($user->id)
            ],
            'mobile' => [
                'nullable',
                'string',
                Rule::unique('users')->ignore($user->id)
            ],
            'phone_number' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('users')->ignore($user->id)
            ],
            'date_of_birth' => 'nullable|date|before:today',
            'location' => 'nullable|string|max:100',
            'gender' => 'nullable|string|in:male,female,other',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];

        // Password update rules (optional)
        if ($this->filled('password')) {
            $rules['password'] = [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
                'confirmed'
            ];
            $rules['password_confirmation'] = 'required|string';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'first_name.min' => 'First name must be at least 2 characters.',
            'first_name.max' => 'First name cannot exceed 50 characters.',
            'last_name.min' => 'Last name must be at least 2 characters.',
            'last_name.max' => 'Last name cannot exceed 50 characters.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already in use.',
            'username.min' => 'Username must be at least 3 characters.',
            'username.max' => 'Username cannot exceed 50 characters.',
            'username.alpha_dash' => 'Username can only contain letters, numbers, dashes, and underscores.',
            'username.unique' => 'This username is already taken.',
            'mobile.unique' => 'This mobile number is already registered.',
            'phone_number.unique' => 'This phone number is already registered.',
            'phone_number.max' => 'Phone number cannot exceed 20 characters.',
            'date_of_birth.before' => 'Date of birth must be before today.',
            'location.max' => 'Location cannot exceed 100 characters.',
            'gender.in' => 'Please select a valid gender.',
            'avatar.image' => 'Avatar must be an image file.',
            'avatar.mimes' => 'Avatar must be a JPEG, PNG, JPG, or GIF file.',
            'avatar.max' => 'Avatar file size cannot exceed 2MB.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }

    /**
     * Translate fields with user friendly name.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'email' => 'email',
            'username' => 'username',
            'mobile' => 'mobile',
            'phone_number' => 'phone number',
            'date_of_birth' => 'date of birth',
            'location' => 'location',
            'gender' => 'gender',
            'avatar' => 'avatar',
            'password' => 'password',
            'password_confirmation' => 'password confirmation',
        ];
    }
}
