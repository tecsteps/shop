<?php

namespace App\Http\Requests;

use App\Models\Page;

class UpdatePageRequest extends StorePageRequest
{
    public function authorize(): bool
    {
        $page = $this->route('page');
        $page = $page instanceof Page ? $page : Page::withoutGlobalScopes()
            ->where('store_id', app('current_store')->id)
            ->find($this->route('pageId'));

        return $page !== null && ($this->user()?->can('update', $page) ?? false);
    }

    public function rules(): array
    {
        return ['title' => ['sometimes', 'string', 'max:255'], ...collect(parent::rules())->except('title')->all()];
    }
}
