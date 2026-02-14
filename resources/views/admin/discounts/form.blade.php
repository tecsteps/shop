@extends('admin.layout')

@section('title', $mode === 'create' ? 'Create Discount' : 'Edit Discount')

@section('content')
@php
    $isEdit = $mode === 'edit' && $discount !== null;
    $formAction = '#';

    if ($isEdit && \Illuminate\Support\Facades\Route::has('admin.discounts.update')) {
        $formAction = route('admin.discounts.update', ['discount' => $discount->id]);
    }

    if (! $isEdit && \Illuminate\Support\Facades\Route::has('admin.discounts.store')) {
        $formAction = route('admin.discounts.store');
    }

    $startsAt = old('starts_at');

    if ($startsAt === null && $discount?->starts_at !== null) {
        $startsAt = $discount->starts_at->format('Y-m-d\\TH:i');
    }

    $endsAt = old('ends_at');

    if ($endsAt === null && $discount?->ends_at !== null) {
        $endsAt = $discount->ends_at->format('Y-m-d\\TH:i');
    }

    $rulesJson = old('rules_json');

    if ($rulesJson === null) {
        if ($discount !== null) {
            $rulesJson = json_encode($discount->rules_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } else {
            $rulesJson = '{}';
        }
    }
@endphp

<div class="rounded-xl border border-slate-200 bg-white p-6">
    <h2 class="text-lg font-semibold text-slate-900">{{ $isEdit ? 'Edit Discount' : 'Create Discount' }}</h2>
    <p class="mt-2 text-sm text-slate-600">Configure discount behavior and targeting rules.</p>

    @if ($errors->any())
        <div class="mt-4 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <p class="font-medium">Please fix the following errors:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($formAction === '#')
        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Discount submission route is not wired yet.
        </div>
    @endif

    <form method="POST" action="{{ $formAction }}" class="mt-6 space-y-5">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="grid gap-5 lg:grid-cols-2">
            <div>
                <label for="type" class="block text-sm font-medium text-slate-700">Type</label>
                <select id="type" name="type" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                    @foreach (['code', 'automatic'] as $type)
                        <option value="{{ $type }}" @selected(old('type', $discount ? (is_object($discount->type) ? $discount->type->value : $discount->type) : 'code') === $type)>
                            {{ ucfirst($type) }}
                        </option>
                    @endforeach
                </select>
                @error('type')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="code" class="block text-sm font-medium text-slate-700">Code</label>
                <input id="code" name="code" type="text" class="mt-1 w-full rounded border border-slate-300 px-3 py-2 uppercase" value="{{ old('code', $discount?->code) }}" placeholder="WELCOME10">
                <p class="mt-1 text-xs text-slate-500">Leave empty for automatic discounts.</p>
                @error('code')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="value_type" class="block text-sm font-medium text-slate-700">Value Type</label>
                <select id="value_type" name="value_type" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                    @foreach (['fixed', 'percent', 'free_shipping'] as $valueType)
                        <option value="{{ $valueType }}" @selected(old('value_type', $discount ? (is_object($discount->value_type) ? $discount->value_type->value : $discount->value_type) : 'fixed') === $valueType)>
                            {{ ucfirst(str_replace('_', ' ', $valueType)) }}
                        </option>
                    @endforeach
                </select>
                @error('value_type')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="value_amount" class="block text-sm font-medium text-slate-700">Value Amount</label>
                <input id="value_amount" name="value_amount" type="number" min="0" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2" value="{{ old('value_amount', $discount?->value_amount ?? 0) }}">
                @error('value_amount')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="starts_at" class="block text-sm font-medium text-slate-700">Starts At</label>
                <input id="starts_at" name="starts_at" type="datetime-local" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2" value="{{ $startsAt }}">
                @error('starts_at')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="ends_at" class="block text-sm font-medium text-slate-700">Ends At</label>
                <input id="ends_at" name="ends_at" type="datetime-local" class="mt-1 w-full rounded border border-slate-300 px-3 py-2" value="{{ $endsAt }}">
                @error('ends_at')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="usage_limit" class="block text-sm font-medium text-slate-700">Usage Limit</label>
                <input id="usage_limit" name="usage_limit" type="number" min="1" class="mt-1 w-full rounded border border-slate-300 px-3 py-2" value="{{ old('usage_limit', $discount?->usage_limit) }}">
                @error('usage_limit')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="usage_count" class="block text-sm font-medium text-slate-700">Usage Count</label>
                <input id="usage_count" name="usage_count" type="number" min="0" class="mt-1 w-full rounded border border-slate-300 px-3 py-2" value="{{ old('usage_count', $discount?->usage_count ?? 0) }}">
                @error('usage_count')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div class="lg:col-span-2">
                <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
                <select id="status" name="status" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                    @foreach (['draft', 'active', 'expired', 'disabled'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $discount ? (is_object($discount->status) ? $discount->status->value : $discount->status) : 'active') === $status)>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
                @error('status')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label for="rules_json" class="block text-sm font-medium text-slate-700">Rules JSON</label>
            <textarea id="rules_json" name="rules_json" rows="8" class="mt-1 w-full rounded border border-slate-300 px-3 py-2 font-mono text-sm">{{ $rulesJson }}</textarea>
            @error('rules_json')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-4">
            <a href="{{ route('admin.discounts.index') }}" class="rounded border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Back</a>

            <button type="submit" class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:cursor-not-allowed disabled:bg-slate-400" @disabled($formAction === '#')>
                {{ $isEdit ? 'Save Discount' : 'Create Discount' }}
            </button>
        </div>
    </form>

    @if ($isEdit && \Illuminate\Support\Facades\Route::has('admin.discounts.destroy'))
        <form method="POST" action="{{ route('admin.discounts.destroy', ['discount' => $discount->id]) }}" class="mt-4" onsubmit="return confirm('Delete this discount?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded border border-rose-300 px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50">
                Delete Discount
            </button>
        </form>
    @endif
</div>
@endsection
