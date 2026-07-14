<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class InviteStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && Gate::forUser($this->user())->allows('manage-staff');
    }

    public function rules(): array
    {
        return ['email' => ['required', 'email', 'max:255'], 'role' => ['required', 'in:admin,staff,support']];
    }
}
