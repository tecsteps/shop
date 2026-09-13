<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => 'required|file|mimetypes:image/jpeg,image/png,image/webp,image/avif,video/mp4|max:51200',
            'alt_text' => 'nullable|string|max:255',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'The file field is required.',
            'file.mimetypes' => 'The file must be a JPEG, PNG, WebP, AVIF image or MP4 video.',
            'file.max' => 'The file must not be larger than 50 MB.',
        ];
    }
}
