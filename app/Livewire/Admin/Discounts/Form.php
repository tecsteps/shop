<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Discount;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.admin.layout.app')]
class Form extends Component
{
    public string $mode = 'create';

    public ?Discount $discount = null;

    public string $code = '';

    public string $type = 'code';

    public string $valueType = 'percent';

    public int $valueAmount = 0;

    public string $startsAt = '';

    public string $endsAt = '';

    public ?int $usageLimit = null;

    public int $minPurchaseAmount = 0;

    public string $status = 'active';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $storeId = app()->bound('current_store') ? app('current_store')->id : null;

        return [
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('discounts', 'code')
                    ->where('store_id', $storeId)
                    ->ignore($this->discount?->id),
            ],
            'type' => ['required', 'string'],
            'valueType' => ['required', 'string'],
            'valueAmount' => ['required', 'integer', 'min:0'],
            'startsAt' => ['nullable', 'date'],
            'endsAt' => ['nullable', 'date'],
            'status' => ['required', 'string'],
        ];
    }

    public function mount(?Discount $discount = null): void
    {
        if ($discount && $discount->exists) {
            $this->mode = 'edit';
            $this->discount = $discount;
            $this->code = $discount->code;
            $this->type = $discount->type->value;
            $this->valueType = $discount->value_type->value;
            $this->valueAmount = $discount->value_amount;
            $this->startsAt = $discount->starts_at?->format('Y-m-d') ?? '';
            $this->endsAt = $discount->ends_at?->format('Y-m-d') ?? '';
            $this->usageLimit = $discount->usage_limit;
            $this->minPurchaseAmount = $discount->rules_json['min_purchase_amount'] ?? 0;
            $this->status = $discount->status->value;
        }
    }

    public function save(): void
    {
        $this->validate();

        $rulesJson = [];
        if ($this->minPurchaseAmount > 0) {
            $rulesJson['min_purchase_amount'] = $this->minPurchaseAmount;
        }

        $data = [
            'code' => strtoupper($this->code),
            'type' => $this->type,
            'value_type' => $this->valueType,
            'value_amount' => $this->valueAmount,
            'starts_at' => $this->startsAt !== '' ? $this->startsAt : now()->toDateString(),
            'ends_at' => $this->endsAt !== '' ? $this->endsAt : now()->addYear()->toDateString(),
            'usage_limit' => $this->usageLimit,
            'rules_json' => $rulesJson,
            'status' => $this->status,
        ];

        if ($this->mode === 'edit' && $this->discount) {
            $this->discount->update($data);
            $discount = $this->discount;
        } else {
            $discount = Discount::create($data);
        }

        $this->redirect(route('admin.discounts.edit', $discount), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.discounts.form');
    }
}
