<?php

namespace App\Livewire\Admin\Discounts;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Discount;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Edit extends Component
{
    public ?int $discountId = null;

    public string $type = 'code';

    public string $code = '';

    public string $valueType = 'percent';

    public int $valueAmount = 0;

    public ?string $startsAt = null;

    public ?string $endsAt = null;

    public ?int $usageLimit = null;

    public string $status = 'active';

    public function mount(?int $discount = null): void
    {
        if ($discount !== null) {
            $model = Discount::query()->findOrFail($discount);
            $this->authorize('view', $model);
            $this->discountId = (int) $model->getKey();
            $this->type = $model->type->value;
            $this->code = (string) ($model->code ?? '');
            $this->valueType = $model->value_type->value;
            $this->valueAmount = (int) $model->value_amount;
            $this->startsAt = optional($model->starts_at)->format('Y-m-d\TH:i');
            $this->endsAt = optional($model->ends_at)->format('Y-m-d\TH:i');
            $this->usageLimit = $model->usage_limit;
            $this->status = $model->status->value;

            return;
        }

        $this->authorize('create', Discount::class);
        $this->startsAt = now()->format('Y-m-d\TH:i');
    }

    public function save(): mixed
    {
        $this->validate([
            'type' => ['required', 'in:code,automatic'],
            'code' => ['nullable', 'string', 'max:255', 'required_if:type,code'],
            'valueType' => ['required', 'in:percent,fixed,free_shipping'],
            'valueAmount' => ['required', 'integer', 'min:0'],
            'startsAt' => ['required', 'date'],
            'endsAt' => ['nullable', 'date', 'after:startsAt'],
            'usageLimit' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'in:draft,active,expired,disabled'],
        ]);

        $storeId = (int) app('current_store')->getKey();

        $data = [
            'type' => DiscountType::from($this->type)->value,
            'code' => $this->type === 'code' ? $this->code : null,
            'value_type' => DiscountValueType::from($this->valueType)->value,
            'value_amount' => $this->valueAmount,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt !== '' ? $this->endsAt : null,
            'usage_limit' => $this->usageLimit,
            'status' => DiscountStatus::from($this->status)->value,
            'rules_json' => null,
        ];

        if ($this->discountId !== null) {
            $model = Discount::query()->findOrFail($this->discountId);
            $this->authorize('update', $model);
            $model->fill($data);
            $model->save();
            session()->flash('status', 'Discount updated.');

            return redirect('/admin/discounts/'.$model->getKey().'/edit');
        }

        $this->authorize('create', Discount::class);
        $created = new Discount($data);
        $created->store_id = $storeId;
        $created->usage_count = 0;
        $created->save();

        session()->flash('status', 'Discount created.');

        return redirect('/admin/discounts/'.$created->getKey().'/edit');
    }

    public function render(): View
    {
        return view('livewire.admin.discounts.edit');
    }
}
