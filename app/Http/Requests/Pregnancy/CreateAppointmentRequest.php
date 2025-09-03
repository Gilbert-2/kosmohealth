<?php

namespace App\Http\Requests\Pregnancy;

use Illuminate\Foundation\Http\FormRequest;

class CreateAppointmentRequest extends FormRequest
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
            'appointment_date' => 'required|date|after:now',
            'appointment_type' => 'required|string|max:100',
            'note' => 'nullable|string|max:1000'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'appointment_type.required' => 'Appointment type is required.',
            'appointment_type.max' => 'Appointment type cannot exceed 100 characters.',
            'doctor_name.max' => 'Doctor name cannot exceed 255 characters.',
            'clinic_name.max' => 'Clinic name cannot exceed 255 characters.',
            'clinic_address.max' => 'Clinic address cannot exceed 500 characters.',
            'clinic_phone.max' => 'Clinic phone cannot exceed 20 characters.',
            'appointment_date.required' => 'Appointment date is required.',
            'appointment_date.after' => 'Appointment date must be in the future.',
            'notes.max' => 'Notes cannot exceed 1000 characters.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'appointment_type' => 'appointment type',
            'doctor_name' => 'doctor name',
            'clinic_name' => 'clinic name',
            'clinic_address' => 'clinic address',
            'clinic_phone' => 'clinic phone',
            'appointment_date' => 'appointment date',
            'notes' => 'notes',
            'appointment_data' => 'appointment data'
        ];
    }
} 