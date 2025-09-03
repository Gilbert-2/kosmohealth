<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rule;

class UploadImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Add proper authorization logic if needed
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'image' => [
                'required',
                'file',
                // Support a wide range of common image formats including modern ones
                'mimetypes:image/jpeg,image/png,image/gif,image/webp,image/svg+xml,image/avif,image/bmp,image/tiff,image/x-icon,image/heic,image/heif',
                'max:5120', // 5MB in kilobytes
            ]
        ];
    }

    /**
     * Get custom validation messages
     *
     * @return array
     */
    public function messages()
    {
        return [
            'image.required' => 'Please select an image to upload.',
            'image.mimetypes' => 'Unsupported image type. Allowed: JPEG, PNG, GIF, WebP, SVG, AVIF, HEIC/HEIF, BMP, TIFF, ICO.',
            'image.max' => 'The image size must not exceed 5MB.',
        ];
    }

    /**
     * Get custom attribute names
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'image' => 'image file'
        ];
    }

    /**
     * Prepare the data for validation
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Log upload attempt for security monitoring
        \Log::info('Image upload attempt', [
            'ip' => $this->ip(),
            'user_agent' => $this->userAgent(),
            'user_id' => auth()->id() ?? 'guest',
            'timestamp' => now()
        ]);
    }
}
