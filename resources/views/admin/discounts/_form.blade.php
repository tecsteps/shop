<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <section class="rounded-lg border border-zinc-200 bg-white p-5">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Discount Setup</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Type</span>
                    <select name="type" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}" @selected(old('type', $discount?->type?->value ?? \App\Enums\DiscountType::Code->value) === $type->value)>
                                {{ ucfirst($type->value) }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Code (optional for automatic)</span>
                    <input type="text" name="code" value="{{ old('code', $discount->code ?? '') }}" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Value type</span>
                    <select name="value_type" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                        @foreach ($valueTypes as $valueType)
                            <option value="{{ $valueType->value }}" @selected(old('value_type', $discount?->value_type?->value ?? \App\Enums\DiscountValueType::Percent->value) === $valueType->value)>
                                {{ $valueType->value }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Value amount</span>
                    <input type="number" min="0" name="value_amount" value="{{ old('value_amount', $discount->value_amount ?? 0) }}" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Start datetime</span>
                    <input
                        type="datetime-local"
                        name="starts_at"
                        value="{{ old('starts_at', optional($discount?->starts_at)->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}"
                        class="w-full rounded-md border border-zinc-300 px-3 py-2"
                    >
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">End datetime</span>
                    <input
                        type="datetime-local"
                        name="ends_at"
                        value="{{ old('ends_at', optional($discount?->ends_at)->format('Y-m-d\TH:i')) }}"
                        class="w-full rounded-md border border-zinc-300 px-3 py-2"
                    >
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Usage limit</span>
                    <input type="number" min="1" name="usage_limit" value="{{ old('usage_limit', $discount->usage_limit ?? '') }}" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Status</span>
                    <select name="status" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(old('status', $discount?->status?->value ?? \App\Enums\DiscountStatus::Active->value) === $status->value)>
                                {{ $status->value }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="block text-sm sm:col-span-2">
                    <span class="mb-1 block text-zinc-700">Rules JSON (optional)</span>
                    <textarea name="rules_json_text" rows="6" class="w-full rounded-md border border-zinc-300 px-3 py-2">{{ old('rules_json_text', isset($discount) ? json_encode($discount->rules_json, JSON_PRETTY_PRINT) : '{}') }}</textarea>
                </label>
            </div>
        </section>
    </div>
</div>
