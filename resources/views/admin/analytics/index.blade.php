@extends('admin.layout')

@section('title', 'Analytics')

@section('content')
<div class="rounded-xl border border-slate-200 bg-white overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600"><tr><th class="px-4 py-2 text-left">Date</th><th class="px-4 py-2 text-right">Orders</th><th class="px-4 py-2 text-right">Revenue</th><th class="px-4 py-2 text-right">Visits</th><th class="px-4 py-2 text-right">Conversion</th></tr></thead>
        <tbody>
            @forelse ($daily as $row)
                @php($conversion = ((int) $row->visits_count) > 0 ? (((int) $row->checkout_completed_count) / ((int) $row->visits_count)) * 100 : 0)
                <tr class="border-t border-slate-100">
                    <td class="px-4 py-2">{{ $row->date }}</td>
                    <td class="px-4 py-2 text-right">{{ (int) $row->orders_count }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format(((int) $row->revenue_amount) / 100, 2, '.', ',') }}</td>
                    <td class="px-4 py-2 text-right">{{ (int) $row->visits_count }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format($conversion, 2) }}%</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No analytics data available.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
