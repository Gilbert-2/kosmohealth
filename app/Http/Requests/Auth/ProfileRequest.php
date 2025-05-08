<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileRequest extends FormRequest
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
        $user = \Auth::user();

        return [
            'name'       => 'required|min:2',
            'birth_date' => 'nullable|date',
            'gender'     => 'nullable|array',
            'mobile'     => array(
                'nullable',
                Rule::unique('users')->ignore($user->id)
            ),
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
            'name'       => __('user.props.name'),
            'birth_date' => __('user.props.birth_date'),
            'gender'     => __('user.props.gender'),
            'mobile'     => __('user.props.mobile'),
        ];
    }
}
