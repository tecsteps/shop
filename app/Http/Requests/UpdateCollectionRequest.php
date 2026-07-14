<?php

namespace App\Http\Requests;

use App\Models\Collection;

class UpdateCollectionRequest extends StoreCollectionRequest
{
    public function authorize(): bool
    {
        $collection = $this->route('collection');
        $collection = $collection instanceof Collection ? $collection : Collection::withoutGlobalScopes()
            ->where('store_id', app('current_store')->id)
            ->find($this->route('collectionId'));

        return $collection !== null && ($this->user()?->can('update', $collection) ?? false);
    }

    public function rules(): array
    {
        return ['title' => ['sometimes', 'string', 'max:255'], ...collect(parent::rules())->except('title')->all()];
    }
}
