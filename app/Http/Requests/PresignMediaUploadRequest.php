<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PresignMediaUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user('sanctum')?->tokenCan('write-products');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $contentType = (string) $this->input('content_type');
        $maxBytes = $contentType === 'video/mp4' ? 500 * 1024 * 1024 : 50 * 1024 * 1024;

        return [
            'filename' => ['required', 'string', 'max:255', 'regex:/\.[a-z0-9]{2,5}$/i'],
            'content_type' => ['required', 'in:image/jpeg,image/png,image/webp,image/avif,video/mp4'],
            'byte_size' => ['required', 'integer', 'min:1', 'max:'.$maxBytes],
        ];
    }
}
