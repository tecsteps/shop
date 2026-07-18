<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Discount;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Discount')]
class Form extends Component
{
    public ?Discount $discount = null;

    public string $type = 'code';

    public string $code = '';

    public string $valueType = 'percent';

    public int $valueAmount = 10;

    public ?int $minimumPurchaseAmount = null;

    public ?int $usageLimit = null;

    public bool $onePerCustomer = false;

    public string $startsAt = '';

    public ?string $endsAt = null;

    public bool $isActive = true;

    public function mount(?Discount $discount = null): void
    {
        $this->discount = $discount?->exists ? $discount : null;
        $this->startsAt = now()->format('Y-m-d\TH:i');

        if ($this->discount) {
            Gate::authorize('update', $discount);
            $rules = $discount->rules_json ?? [];
            $this->fill(['type' => $discount->type->value, 'code' => $discount->code ?? '', 'valueType' => $discount->value_type->value, 'valueAmount' => $discount->value_amount, 'minimumPurchaseAmount' => $rules['minimum_purchase_amount'] ?? null, 'usageLimit' => $discount->usage_limit, 'onePerCustomer' => $rules['once_per_customer'] ?? false, 'startsAt' => $discount->starts_at?->format('Y-m-d\TH:i') ?? '', 'endsAt' => $discount->ends_at?->format('Y-m-d\TH:i'), 'isActive' => $discount->status->value === 'active']);
        } else {
            Gate::authorize('create', Discount::class);
        }
    }

    public function generateCode(): void
    {
        $this->code = Str::upper(Str::random(10));
    }

    public function save(): void
    {
        $validated = $this->validate([
            'type' => ['required', Rule::in(['code', 'automatic'])], 'code' => [Rule::requiredIf($this->type === 'code'), 'nullable', 'string', 'max:50', Rule::unique('discounts', 'code')->where('store_id', app('current_store')->id)->ignore($this->discount?->id)],
            'valueType' => ['required', Rule::in(['percent', 'fixed', 'free_shipping'])], 'valueAmount' => ['required', 'integer', 'min:0'],
            'minimumPurchaseAmount' => ['nullable', 'integer', 'min:0'], 'usageLimit' => ['nullable', 'integer', 'min:1'], 'onePerCustomer' => ['boolean'],
            'startsAt' => ['required', 'date'], 'endsAt' => ['nullable', 'date', 'after:startsAt'], 'isActive' => ['boolean'],
        ]);
        $data = ['store_id' => app('current_store')->id, 'type' => $validated['type'], 'code' => $validated['type'] === 'code' ? Str::upper($validated['code']) : null, 'value_type' => $validated['valueType'], 'value_amount' => $validated['valueAmount'], 'starts_at' => $validated['startsAt'], 'ends_at' => $validated['endsAt'], 'usage_limit' => $validated['usageLimit'], 'status' => $validated['isActive'] ? 'active' : 'disabled', 'rules_json' => ['minimum_purchase_amount' => $validated['minimumPurchaseAmount'], 'once_per_customer' => $validated['onePerCustomer']]];
        $this->discount ? $this->discount->update($data) : $this->discount = Discount::query()->create($data);
        session()->flash('toast', 'Discount saved.');
        $this->redirectRoute('admin.discounts.edit', ['discount' => $this->discount], navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.discounts.form');
    }
}
