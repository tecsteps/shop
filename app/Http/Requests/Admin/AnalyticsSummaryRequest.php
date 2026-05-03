<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AnalyticsSummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'granularity' => ['nullable', Rule::in(['day', 'week', 'month'])],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->filled('from') || ! $this->filled('to')) {
                    return;
                }

                $from = Carbon::parse((string) $this->input('from'));
                $to = Carbon::parse((string) $this->input('to'));

                if ($from->diffInDays($to) > 365) {
                    $validator->errors()->add('to', 'The date range may not exceed 365 days.');
                }
            },
        ];
    }
}
