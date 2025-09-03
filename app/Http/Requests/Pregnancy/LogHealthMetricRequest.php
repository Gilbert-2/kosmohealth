<?php

namespace App\Http\Requests\Pregnancy;

use Illuminate\Foundation\Http\FormRequest;

class LogHealthMetricRequest extends FormRequest
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
            'weight' => 'nullable|string|max:10',
            'blood_pressure' => 'nullable|string|max:20',
            'water_intake' => 'nullable|string|max:10',
            'sleep' => 'nullable|string|max:10',
            'nutrition' => 'nullable|string|max:20',
            'exercise' => 'nullable|string|max:20',
            'mood' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:1000'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'measurement_date.before_or_equal' => 'Measurement date cannot be in the future.',
            'weight_kg.numeric' => 'Weight must be a number.',
            'weight_kg.min' => 'Weight must be at least 30 kg.',
            'weight_kg.max' => 'Weight cannot exceed 300 kg.',
            'blood_pressure_systolic.numeric' => 'Systolic blood pressure must be a number.',
            'blood_pressure_systolic.min' => 'Systolic blood pressure must be at least 70.',
            'blood_pressure_systolic.max' => 'Systolic blood pressure cannot exceed 200.',
            'blood_pressure_diastolic.numeric' => 'Diastolic blood pressure must be a number.',
            'blood_pressure_diastolic.min' => 'Diastolic blood pressure must be at least 40.',
            'blood_pressure_diastolic.max' => 'Diastolic blood pressure cannot exceed 130.',
            'blood_sugar.numeric' => 'Blood sugar must be a number.',
            'blood_sugar.min' => 'Blood sugar must be at least 50.',
            'blood_sugar.max' => 'Blood sugar cannot exceed 500.',
            'fundal_height_cm.numeric' => 'Fundal height must be a number.',
            'fundal_height_cm.min' => 'Fundal height must be at least 0 cm.',
            'fundal_height_cm.max' => 'Fundal height cannot exceed 50 cm.',
            'bmi.numeric' => 'BMI must be a number.',
            'bmi.min' => 'BMI must be at least 15.',
            'bmi.max' => 'BMI cannot exceed 60.',
            'notes.max' => 'Notes cannot exceed 1000 characters.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'measurement_date' => 'measurement date',
            'weight_kg' => 'weight',
            'blood_pressure_systolic' => 'systolic blood pressure',
            'blood_pressure_diastolic' => 'diastolic blood pressure',
            'blood_sugar' => 'blood sugar',
            'fundal_height_cm' => 'fundal height',
            'bmi' => 'BMI',
            'vital_signs' => 'vital signs',
            'lab_results' => 'lab results',
            'notes' => 'notes'
        ];
    }
} 