@extends('admin.layout')

@section('title', 'Developers')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-zinc-900">Developers</h1>
        <p class="mt-1 text-sm text-zinc-600">Manage API tokens and webhook subscriptions.</p>
    </div>

    @if ($newToken !== '')
        <div class="mb-4 rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <p class="font-semibold">New token (shown once):</p>
            <code class="mt-1 block break-all rounded border border-amber-200 bg-white px-2 py-1 text-xs">{{ $newToken }}</code>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-lg border border-zinc-200 bg-white p-5">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">API Tokens</h2>

            @if ($tokenTable)
                <p class="mt-1 text-xs text-zinc-500">Source table: <code>{{ $tokenTable }}</code></p>
            @else
                <p class="mt-2 text-sm text-zinc-600">Token schema is not available yet.</p>
            @endif

            @if ($tokenCrudAvailable)
                <form method="POST" action="{{ route('admin.developers.tokens.store') }}" class="mt-4 grid gap-3">
                    @csrf

                    <label class="block text-sm">
                        <span class="mb-1 block text-zinc-700">Token name</span>
                        <input type="text" name="name" value="{{ old('name') }}" required class="w-full rounded-md border border-zinc-300 px-3 py-2">
                    </label>

                    @if ($installationOptions->isNotEmpty())
                        <label class="block text-sm">
                            <span class="mb-1 block text-zinc-700">App installation</span>
                            <select name="installation_id" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                                <option value="">Auto (latest)</option>
                                @foreach ($installationOptions as $installationOption)
                                    <option value="{{ $installationOption->id }}" @selected((string) old('installation_id') === (string) $installationOption->id)>
                                        {{ $installationOption->label }}{{ $installationOption->status ? ' ('.$installationOption->status.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                    @endif

                    <label class="block text-sm">
                        <span class="mb-1 block text-zinc-700">Scopes (comma or newline separated)</span>
                        <textarea name="scopes" rows="3" class="w-full rounded-md border border-zinc-300 px-3 py-2 font-mono text-xs">{{ old('scopes') }}</textarea>
                    </label>

                    <label class="block text-sm">
                        <span class="mb-1 block text-zinc-700">Expires at (optional)</span>
                        <input type="date" name="expires_at" value="{{ old('expires_at') }}" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                    </label>

                    <button type="submit" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700">
                        Create Token
                    </button>
                </form>
            @endif

            <div class="mt-4 overflow-hidden rounded-lg border border-zinc-200">
                <table class="min-w-full divide-y divide-zinc-200 text-sm">
                    <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500">
                        <tr>
                            <th class="px-3 py-2">Token</th>
                            <th class="px-3 py-2">Scopes</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 bg-white">
                        @forelse ($tokens as $token)
                            <tr>
                                <td class="px-3 py-2">
                                    <div class="font-medium text-zinc-900">{{ $token->name }}</div>
                                    <div class="text-xs text-zinc-500">ID {{ $token->id }}</div>
                                </td>
                                <td class="px-3 py-2 text-xs text-zinc-600">
                                    {{ $token->scopes !== [] ? implode(', ', $token->scopes) : '-' }}
                                </td>
                                <td class="px-3 py-2 text-xs">
                                    @if ($token->revoked)
                                        <span class="text-rose-700">Revoked</span>
                                    @else
                                        <span class="text-emerald-700">Active</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right">
                                    @if (! $token->revoked && $tokenCrudAvailable)
                                        <form method="POST" action="{{ route('admin.developers.tokens.destroy', $token->id) }}" onsubmit="return confirm('Revoke this token?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-md border border-rose-300 bg-white px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50">
                                                Revoke
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-zinc-500">No tokens found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-lg border border-zinc-200 bg-white p-5">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Webhook Subscriptions</h2>

            @if ($webhookTable)
                <p class="mt-1 text-xs text-zinc-500">Source table: <code>{{ $webhookTable }}</code></p>
            @else
                <p class="mt-2 text-sm text-zinc-600">Webhook schema is not available yet.</p>
            @endif

            @if ($webhookCrudAvailable)
                <form method="POST" action="{{ route('admin.developers.webhooks.store') }}" class="mt-4 grid gap-3">
                    @csrf

                    <label class="block text-sm">
                        <span class="mb-1 block text-zinc-700">Topic</span>
                        <input type="text" name="topic" value="{{ old('topic') }}" placeholder="order.created" required class="w-full rounded-md border border-zinc-300 px-3 py-2">
                    </label>

                    <label class="block text-sm">
                        <span class="mb-1 block text-zinc-700">Target URL</span>
                        <input type="url" name="url" value="{{ old('url') }}" placeholder="https://example.com/webhooks/orders" required class="w-full rounded-md border border-zinc-300 px-3 py-2">
                    </label>

                    @if ($installationOptions->isNotEmpty())
                        <label class="block text-sm">
                            <span class="mb-1 block text-zinc-700">App installation (optional)</span>
                            <select name="installation_id" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                                <option value="">None</option>
                                @foreach ($installationOptions as $installationOption)
                                    <option value="{{ $installationOption->id }}" @selected((string) old('installation_id') === (string) $installationOption->id)>
                                        {{ $installationOption->label }}{{ $installationOption->status ? ' ('.$installationOption->status.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                    @endif

                    <label class="block text-sm">
                        <span class="mb-1 block text-zinc-700">Secret (optional)</span>
                        <input type="text" name="secret" value="{{ old('secret') }}" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                    </label>

                    <label class="inline-flex items-center gap-2 text-sm text-zinc-700">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-zinc-300 text-zinc-900">
                        Active
                    </label>

                    <button type="submit" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700">
                        Create Webhook
                    </button>
                </form>
            @endif

            <div class="mt-4 space-y-3">
                @forelse ($webhooks as $webhook)
                    <div class="rounded-lg border border-zinc-200 p-3">
                        @if ($webhookCrudAvailable)
                            <form method="POST" action="{{ route('admin.developers.webhooks.update', $webhook->id) }}" class="grid gap-2 md:grid-cols-[1fr_1fr_auto_auto] md:items-end">
                                @csrf
                                @method('PUT')

                                <label class="block text-xs text-zinc-600">
                                    <span class="mb-1 block">Topic</span>
                                    <input type="text" name="topic" value="{{ $webhook->topic }}" class="w-full rounded-md border border-zinc-300 px-2 py-1.5 text-sm">
                                </label>

                                <label class="block text-xs text-zinc-600">
                                    <span class="mb-1 block">URL</span>
                                    <input type="url" name="url" value="{{ $webhook->url }}" class="w-full rounded-md border border-zinc-300 px-2 py-1.5 text-sm">
                                </label>

                                <label class="inline-flex items-center gap-2 text-xs text-zinc-700">
                                    <input type="checkbox" name="is_active" value="1" @checked($webhook->is_active) class="rounded border-zinc-300 text-zinc-900">
                                    Active
                                </label>

                                <button type="submit" class="rounded-md border border-zinc-300 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-100">
                                    Save
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.developers.webhooks.destroy', $webhook->id) }}" class="mt-2" onsubmit="return confirm('Delete this webhook?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-md border border-rose-300 bg-white px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50">
                                    Delete
                                </button>
                            </form>
                        @else
                            <div class="text-sm font-medium text-zinc-900">{{ $webhook->topic }}</div>
                            <div class="text-xs text-zinc-600">{{ $webhook->url }}</div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-lg border border-zinc-200 px-3 py-6 text-center text-sm text-zinc-500">
                        No webhook subscriptions found.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
