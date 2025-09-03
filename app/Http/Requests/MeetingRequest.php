<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MeetingRequest extends FormRequest
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
        $uuid = $this->route('meeting');

        $rules = [
            'identifier' => 'nullable|alpha_dash',
            // Accept either object with uuid or plain string uuid
            'type'       => ['required'],
        ];

        if (request('instant')) {
            return $rules;
        }

        if (request('should_remind')) {
            $rules['remind_before'] = 'required|integer|min:1';
        }

        $rules['title']           = 'required|min:5';
        $rules['agenda']          = 'required|min:20';
        $rules['start_date_time'] = 'required|date';
        $rules['period']          = 'integer|min:1';
        // Accept either object with uuid or plain string uuid, allow nullable
        $rules['category']        = ['nullable'];

        // Optional: patient to notify upon creation
        $rules['patient_id']      = 'nullable|exists:users,id';

        return $rules;
    }
}
