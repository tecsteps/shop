<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Discount;
use App\Models\Store;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Form extends Component
{
    public ?Discount $discount = null;

    public string $mode = 'create';

    #[Validate('required|string|in:code,automatic')]
    public string $type = 'code';

    #[Validate('nullable|string|max:100')]
    public string $code = '';

    #[Validate('required|string|in:percent,fixed,free_shipping')]
    public string $valueType = 'percent';

    #[Validate('nullable|integer|min:0')]
    public int $valueAmount = 0;

    #[Validate('nullable|date')]
    public ?string $startsAt = null;

    #[Validate('nullable|date')]
    public ?string $endsAt = null;

    #[Validate('nullable|integer|min:1')]
    public ?int $usageLimit = null;

    #[Validate('required|string|in:draft,active,disabled,expired')]
    public string $status = 'draft';

    #[Validate('nullable|integer|min:0')]
    public ?int $minimumPurchase = null;

    public function mount(?Discount $discount = null): void
    {
        if ($discount !== null && $discount->exists) {
            $this->discount = $discount;
            $this->mode = 'edit';
            $this->type = $discount->type->value;
            $this->code = (string) ($discount->code ?? '');
            $this->valueType = $discount->value_type->value;
            $this->valueAmount = (int) $discount->value_amount;
            $this->startsAt = $discount->starts_at?->format('Y-m-d\TH:i');
            $this->endsAt = $discount->ends_at?->format('Y-m-d\TH:i');
            $this->usageLimit = $discount->usage_limit;
            $this->status = $discount->status->value;
            $this->minimumPurchase = $discount->rules_json['minimum_purchase'] ?? null;
        }
    }

    public function save(): mixed
    {
        $this->validate();

        if ($this->type === 'code' && $this->code === '') {
            $this->addError('code', 'Code is required for code-based discounts.');

            return null;
        }

        /** @var Store $store */
        $store = app('current_store');

        $rules = [];
        if ($this->minimumPurchase !== null && $this->minimumPurchase > 0) {
            $rules['minimum_purchase'] = $this->minimumPurchase;
        }

        $data = [
            'store_id' => $store->id,
            'type' => $this->type,
            'code' => $this->type === 'code' ? $this->code : null,
            'value_type' => $this->valueType,
            'value_amount' => $this->valueAmount,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt,
            'usage_limit' => $this->usageLimit,
            'rules_json' => $rules !== [] ? $rules : null,
            'status' => $this->status,
        ];

        if ($this->mode === 'create') {
            Discount::create($data);
        } else {
            $this->discount->update($data);
        }

        session()->flash('status', 'Discount saved.');

        return redirect()->route('admin.discounts.index');
    }

    public function render(): View
    {
        return view('livewire.admin.discounts.form');
    }
}
