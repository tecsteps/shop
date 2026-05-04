<?php

namespace App\Http\Requests\Api\Admin\V1;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CreateProductMediaUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $store = $this->route('store');
        $product = $this->route('product');

        $store = $store instanceof Store ? $store : Store::query()->find($store);

        if (! $store instanceof Store) {
            return false;
        }

        app()->instance('current_store', $store);

        if (! $product instanceof Product || (int) $product->store_id !== $store->getKey()) {
            return true;
        }

        return $this->user()?->can('update', $product) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'filename' => ['required', 'string', 'max:255'],
            'content_type' => ['required', Rule::in(['image/jpeg', 'image/png', 'image/webp', 'image/avif', 'video/mp4'])],
            'byte_size' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->validateExtension($validator);
                $this->validateByteSize($validator);
            },
        ];
    }

    private function validateExtension(Validator $validator): void
    {
        $extension = strtolower(pathinfo((string) $this->input('filename'), PATHINFO_EXTENSION));
        $allowedExtensions = match ((string) $this->input('content_type')) {
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/webp' => ['webp'],
            'image/avif' => ['avif'],
            'video/mp4' => ['mp4'],
            default => [],
        };

        if ($extension === '' || ! in_array($extension, $allowedExtensions, true)) {
            $validator->errors()->add('filename', __('The filename extension must match the content type.'));
        }
    }

    private function validateByteSize(Validator $validator): void
    {
        $limit = $this->input('content_type') === 'video/mp4'
            ? 500 * 1024 * 1024
            : 50 * 1024 * 1024;

        if ((int) $this->input('byte_size') > $limit) {
            $validator->errors()->add('byte_size', __('The uploaded media file is too large.'));
        }
    }
}
