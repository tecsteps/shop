<?php

namespace App\Livewire\Admin\Discounts;

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Models\Discount;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;

    public ?Discount $discount = null;

    public string $type = 'code';

    public string $code = '';

    public string $valueType = 'percent';

    public ?string $valueAmount = null;

    public ?string $minimumPurchaseAmount = null;

    public ?string $usageLimit = null;

    public string $startsAt = '';

    public ?string $endsAt = null;

    public bool $isActive = true;

    public function mount(?Discount $discount = null): void
    {
        if ($discount && $discount->exists) {
            $this->discount = $discount;
            $this->type = $discount->type->value;
            $this->code = $discount->code ?? '';
            $this->valueType = $discount->value_type->value;

            if ($discount->value_type === DiscountValueType::Percent) {
                $this->valueAmount = (string) $discount->value_amount;
            } elseif ($discount->value_type === DiscountValueType::Fixed) {
                $this->valueAmount = (string) number_format($discount->value_amount / 100, 2, '.', '');
            }

            $rules = $discount->rules_json ?? [];
            if (isset($rules['minimum_purchase_amount'])) {
                $this->minimumPurchaseAmount = (string) number_format($rules['minimum_purchase_amount'] / 100, 2, '.', '');
            }

            $this->usageLimit = $discount->usage_limit ? (string) $discount->usage_limit : null;
            $this->startsAt = $discount->starts_at?->format('Y-m-d\TH:i') ?? '';
            $this->endsAt = $discount->ends_at?->format('Y-m-d\TH:i');
            $this->isActive = $discount->status === DiscountStatus::Active;
        } else {
            $this->startsAt = now()->format('Y-m-d\TH:i');
        }
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'in:code,automatic'],
            'code' => $this->type === 'code' ? ['required', 'string', 'max:255'] : ['nullable'],
            'valueType' => ['required', 'in:percent,fixed,free_shipping'],
            'valueAmount' => $this->valueType !== 'free_shipping' ? ['required', 'numeric', 'min:0'] : ['nullable'],
            'minimumPurchaseAmount' => ['nullable', 'numeric', 'min:0'],
            'usageLimit' => ['nullable', 'integer', 'min:1'],
            'startsAt' => ['required', 'date'],
            'endsAt' => ['nullable', 'date', 'after:startsAt'],
            'isActive' => ['boolean'],
        ];
    }

    public function generateCode(): void
    {
        $this->code = strtoupper(Str::random(8));
    }

    public function save(): void
    {
        if ($this->discount) {
            $this->authorize('update', $this->discount);
        } else {
            $this->authorize('create', Discount::class);
        }

        $this->validate();

        $valueAmount = 0;
        if ($this->valueType === 'percent') {
            $valueAmount = (int) $this->valueAmount;
        } elseif ($this->valueType === 'fixed') {
            $valueAmount = (int) round(((float) $this->valueAmount) * 100);
        }

        $rulesJson = [];
        if ($this->minimumPurchaseAmount) {
            $rulesJson['minimum_purchase_amount'] = (int) round(((float) $this->minimumPurchaseAmount) * 100);
        }

        $data = [
            'type' => $this->type,
            'code' => $this->type === 'code' ? $this->code : null,
            'value_type' => $this->valueType,
            'value_amount' => $valueAmount,
            'usage_limit' => $this->usageLimit ? (int) $this->usageLimit : null,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt ?: null,
            'status' => $this->isActive ? DiscountStatus::Active : DiscountStatus::Draft,
            'rules_json' => ! empty($rulesJson) ? $rulesJson : [],
        ];

        if ($this->discount) {
            $this->discount->update($data);
            $this->dispatch('toast', type: 'success', message: 'Discount updated successfully.');
        } else {
            Discount::create($data);
            $this->dispatch('toast', type: 'success', message: 'Discount created successfully.');
            $this->redirect(route('admin.discounts.index'), navigate: true);
        }
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->discount !== null && $this->discount->exists;
    }

    public function render()
    {
        $title = $this->isEditing ? 'Edit Discount' : 'Create Discount';

        return view('livewire.admin.discounts.form')
            ->layout('layouts.admin', ['title' => $title]);
    }
}
