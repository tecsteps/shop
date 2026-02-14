@extends('admin.layout')

@section('title', 'Developers')

@section('content')
<div class="rounded-xl border border-slate-200 bg-white p-6">
    <h2 class="text-lg font-semibold text-slate-900">Developer Integrations</h2>
    <p class="mt-2 text-sm text-slate-600">Manage API tokens and webhook subscriptions for installed apps.</p>

    <div class="mt-4 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600"><tr><th class="px-3 py-2 text-left">Installation</th><th class="px-3 py-2 text-right">Tokens</th><th class="px-3 py-2 text-right">Webhooks</th></tr></thead>
            <tbody>
            @forelse ($installations as $installation)
                <tr class="border-t border-slate-100"><td class="px-3 py-2">{{ $installation->app?->name ?? ('App #'.$installation->app_id) }}</td><td class="px-3 py-2 text-right">{{ $installation->oauthTokens->count() }}</td><td class="px-3 py-2 text-right">{{ $installation->webhookSubscriptions->count() }}</td></tr>
            @empty
                <tr><td colspan="3" class="px-3 py-6 text-center text-slate-500">No app installations.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
