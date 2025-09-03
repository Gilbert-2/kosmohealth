<?php

namespace App\Http\Requests\Pregnancy;

use Illuminate\Foundation\Http\FormRequest;

class StartPregnancyRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'lmp' => 'required|date|before_or_equal:today',
            'due_date' => 'nullable|date|after:today',
            'use_lmp' => 'boolean'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'lmp_date.required' => 'Last menstrual period date is required.',
            'lmp_date.before_or_equal' => 'LMP date cannot be in the future.',
            'ultrasound_date.before_or_equal' => 'Ultrasound date cannot be in the future.',
            'ultrasound_due_date.after' => 'Ultrasound due date must be in the future.',
            'pre_pregnancy_weight.numeric' => 'Pre-pregnancy weight must be a number.',
            'pre_pregnancy_weight.min' => 'Pre-pregnancy weight must be at least 30 kg.',
            'pre_pregnancy_weight.max' => 'Pre-pregnancy weight cannot exceed 300 kg.',
            'notes.max' => 'Notes cannot exceed 1000 characters.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'lmp_date' => 'last menstrual period date',
            'ultrasound_date' => 'ultrasound date',
            'ultrasound_due_date' => 'ultrasound due date',
            'medical_history' => 'medical history',
            'pre_pregnancy_weight' => 'pre-pregnancy weight',
            'notes' => 'notes'
        ];
    }
} 