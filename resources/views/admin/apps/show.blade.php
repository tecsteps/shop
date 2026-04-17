@extends('admin.layout')

@section('title', 'App Installation')

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="rounded-xl border border-slate-200 bg-white p-6 lg:col-span-2">
        <h2 class="text-lg font-semibold text-slate-900">{{ $installation->app?->name ?? ('App #'.$installation->app_id) }}</h2>
        <p class="mt-1 text-sm text-slate-600">Status: {{ is_object($installation->status) ? $installation->status->value : $installation->status }}</p>
        <h3 class="mt-4 text-sm font-semibold text-slate-900">Granted Scopes</h3>
        <pre class="mt-2 overflow-x-auto rounded border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700">{{ json_encode($installation->scopes_json ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
    </div>

    <div class="space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <h3 class="text-sm font-semibold text-slate-900">OAuth Tokens</h3>
            <p class="mt-2 text-sm text-slate-700">{{ $installation->oauthTokens->count() }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <h3 class="text-sm font-semibold text-slate-900">Webhook Subscriptions</h3>
            <p class="mt-2 text-sm text-slate-700">{{ $installation->webhookSubscriptions->count() }}</p>
        </div>
    </div>
</div>
@endsection
