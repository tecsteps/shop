<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Discount;
use Livewire\Component;

class Form extends Component
{
    public ?Discount $discount = null;

    public string $code = '';

    public string $valueType = 'percent';

    public int $valueAmount = 10;

    public string $message = '';

    public function mount(?Discount $discount = null): void
    {
        $this->discount = $discount;

        if ($discount !== null) {
            $this->code = (string) $discount->code;
            $this->valueType = $discount->value_type->value;
            $this->valueAmount = $discount->value_amount;
        }
    }

    public function save(): void
    {
        $data = $this->validate(['code' => ['required', 'string', 'max:64'], 'valueType' => ['required', 'in:percent,fixed,free_shipping'], 'valueAmount' => ['required', 'integer', 'min:0']]);

        $this->authorize($this->discount === null ? 'create' : 'update', $this->discount ?? Discount::class);
        $this->discount = Discount::updateOrCreate(['id' => $this->discount?->id], ['store_id' => app('current_store')->getKey(), 'code' => strtoupper($data['code']), 'type' => 'code', 'value_type' => $data['valueType'], 'value_amount' => $data['valueAmount'], 'status' => 'active', 'starts_at' => now(), 'rules_json' => []]);
        $this->message = 'Discount saved';
    }

    public function render(): mixed
    {
        return view('livewire.admin.discounts.form')->layout('layouts.admin');
    }
}
