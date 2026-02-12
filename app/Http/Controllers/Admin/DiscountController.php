<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Discount;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DiscountController extends AdminController
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));
        $status = (string) $request->string('status', '');

        $discounts = Discount::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('code', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, $this->enumValues(DiscountStatus::class), true), function ($query) use ($status): void {
                $query->where('status', $status);
            })
            ->orderByDesc('starts_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.discounts.index', [
            'discounts' => $discounts,
            'search' => $search,
            'status' => $status,
            'statuses' => DiscountStatus::cases(),
            'currency' => $this->currentStore()->default_currency,
        ]);
    }

    public function create(): View
    {
        return view('admin.discounts.create', [
            'discount' => null,
            'types' => DiscountType::cases(),
            'valueTypes' => DiscountValueType::cases(),
            'statuses' => DiscountStatus::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateDiscount($request);

        Discount::query()->create([
            'type' => $validated['type'],
            'code' => $validated['code'],
            'value_type' => $validated['value_type'],
            'value_amount' => $validated['value_amount'],
            'starts_at' => Carbon::parse($validated['starts_at']),
            'ends_at' => $validated['ends_at'] ? Carbon::parse($validated['ends_at']) : null,
            'usage_limit' => $validated['usage_limit'],
            'usage_count' => 0,
            'rules_json' => $validated['rules_json'],
            'status' => $validated['status'],
        ]);

        return redirect()->route('admin.discounts.index')
            ->with('status', 'Discount created.');
    }

    public function edit(Discount $discount): View
    {
        return view('admin.discounts.edit', [
            'discount' => $discount,
            'types' => DiscountType::cases(),
            'valueTypes' => DiscountValueType::cases(),
            'statuses' => DiscountStatus::cases(),
        ]);
    }

    public function update(Request $request, Discount $discount): RedirectResponse
    {
        $validated = $this->validateDiscount($request, $discount);

        $discount->update([
            'type' => $validated['type'],
            'code' => $validated['code'],
            'value_type' => $validated['value_type'],
            'value_amount' => $validated['value_amount'],
            'starts_at' => Carbon::parse($validated['starts_at']),
            'ends_at' => $validated['ends_at'] ? Carbon::parse($validated['ends_at']) : null,
            'usage_limit' => $validated['usage_limit'],
            'rules_json' => $validated['rules_json'],
            'status' => $validated['status'],
        ]);

        return redirect()->route('admin.discounts.edit', $discount)
            ->with('status', 'Discount updated.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateDiscount(Request $request, ?Discount $discount = null): array
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in($this->enumValues(DiscountType::class))],
            'code' => ['nullable', 'string', 'max:255'],
            'value_type' => ['required', Rule::in($this->enumValues(DiscountValueType::class))],
            'value_amount' => ['required', 'integer', 'min:0'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'rules_json_text' => ['nullable', 'string'],
            'status' => ['required', Rule::in($this->enumValues(DiscountStatus::class))],
        ]);

        $code = trim((string) ($validated['code'] ?? ''));

        if ($validated['type'] === DiscountType::Code->value && $code === '') {
            throw ValidationException::withMessages([
                'code' => 'A discount code is required for code-based discounts.',
            ]);
        }

        if ($validated['type'] === DiscountType::Automatic->value) {
            $code = '';
        }

        $normalizedCode = $code === '' ? null : strtoupper($code);

        if ($normalizedCode !== null) {
            $exists = Discount::query()
                ->where('code', $normalizedCode)
                ->when($discount, fn ($query) => $query->whereKeyNot($discount->id))
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'code' => 'This discount code already exists for the current store.',
                ]);
            }
        }

        $rulesJson = [];
        $rawRulesJson = trim((string) ($validated['rules_json_text'] ?? ''));

        if ($rawRulesJson !== '') {
            $decoded = json_decode($rawRulesJson, true);

            if (! is_array($decoded)) {
                throw ValidationException::withMessages([
                    'rules_json_text' => 'Rules must be valid JSON.',
                ]);
            }

            $rulesJson = $decoded;
        }

        if ($validated['value_type'] === DiscountValueType::FreeShipping->value) {
            $validated['value_amount'] = 0;
        }

        $validated['code'] = $normalizedCode;
        $validated['rules_json'] = $rulesJson;

        unset($validated['rules_json_text']);

        return $validated;
    }
}
