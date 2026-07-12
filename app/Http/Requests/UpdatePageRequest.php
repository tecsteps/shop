<?php

namespace App\Http\Requests;

class UpdatePageRequest extends StorePageRequest
{
    public function rules(): array
    {
        return ['title' => ['sometimes', 'string', 'max:255'], ...collect(parent::rules())->except('title')->all()];
    }
}
