<?php

namespace App\Http\Requests;

use App\Traits\Validation;
use Illuminate\Foundation\Http\FormRequest;

class MeetingPollRequest extends FormRequest
{
    use Validation;

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
        return [
            'question'        => 'required|string|max:255',
            'options'         => 'required|array',
            'options.*.title' => 'required|min:1|max:200|distinct',
            'duration'        => 'required|integer',
            'is_published'    => 'boolean',
            'config'          => 'array'
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
            'options.*.title' => trans('meeting.poll.props.option'),
        ];
    }

    public function withValidator($validator)
    {
        // $validator->after(function ($validator) {
        //     $this->change($validator, 'options', 'message');
        //     $this->simplify($validator, 'options.*.title', 'options.*.title');
        // });
    }

    /**
     * Translate rules with user friendly name.
     *
     * @return array
     */
    public function messages()
    {
        return [];
    }
}
