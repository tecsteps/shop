@extends('admin.layout')

@section('title', 'Shipping Settings')

@section('content')
<div class="space-y-4">
    @forelse ($zones as $zone)
        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-lg font-semibold text-slate-900">{{ $zone->name }}</h2>
            <p class="mt-2 text-sm text-slate-600">Countries: {{ implode(', ', is_array($zone->countries_json) ? $zone->countries_json : []) ?: '—' }}</p>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600"><tr><th class="px-3 py-2 text-left">Rate</th><th class="px-3 py-2 text-left">Type</th><th class="px-3 py-2 text-left">Active</th></tr></thead>
                    <tbody>
                    @forelse ($zone->rates as $rate)
                        <tr class="border-t border-slate-100"><td class="px-3 py-2">{{ $rate->name }}</td><td class="px-3 py-2">{{ is_object($rate->type) ? $rate->type->value : $rate->type }}</td><td class="px-3 py-2">{{ $rate->is_active ? 'Yes' : 'No' }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="px-3 py-4 text-slate-500">No rates configured.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-600">No shipping zones configured.</div>
    @endforelse
</div>
@endsection
