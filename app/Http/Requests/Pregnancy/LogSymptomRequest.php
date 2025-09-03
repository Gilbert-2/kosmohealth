<?php

namespace App\Http\Requests\Pregnancy;

use Illuminate\Foundation\Http\FormRequest;

class LogSymptomRequest extends FormRequest
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
            'symptom' => 'required|string|max:100',
            'severity' => 'required|in:mild,moderate,severe',
            'notes' => 'nullable|string|max:1000'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'symptom_date.before_or_equal' => 'Symptom date cannot be in the future.',
            'symptom_type.required' => 'Symptom type is required.',
            'symptom_type.max' => 'Symptom type cannot exceed 100 characters.',
            'severity.required' => 'Symptom severity is required.',
            'severity.in' => 'Severity must be mild, moderate, or severe.',
            'description.max' => 'Description cannot exceed 1000 characters.',
            'medical_notes.max' => 'Medical notes cannot exceed 1000 characters.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'symptom_date' => 'symptom date',
            'symptom_type' => 'symptom type',
            'severity' => 'severity',
            'description' => 'description',
            'symptom_data' => 'symptom data',
            'requires_medical_attention' => 'requires medical attention',
            'medical_notes' => 'medical notes'
        ];
    }
} 