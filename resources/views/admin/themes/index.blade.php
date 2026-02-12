@extends('admin.layout')

@section('title', 'Themes')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-zinc-900">Themes</h1>
        <p class="mt-1 text-sm text-zinc-600">Manage theme settings and publish the active storefront theme.</p>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500">
                    <tr>
                        <th class="px-4 py-3">Theme</th>
                        <th class="px-4 py-3">Version</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Published</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white">
                    @forelse ($themes as $theme)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium text-zinc-900">{{ $theme->name }}</div>
                                <div class="text-xs text-zinc-500">ID: {{ $theme->id }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $theme->version ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-md border px-2 py-1 text-xs font-medium {{ $theme->status->value === \App\Enums\ThemeStatus::Published->value ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : 'border-zinc-300 bg-zinc-100 text-zinc-700' }}">
                                    {{ ucfirst($theme->status->value) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-zinc-600">{{ optional($theme->published_at)->format('M j, Y g:i A') ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.themes.edit', $theme) }}" class="rounded-md border border-zinc-300 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-100">
                                        Edit Settings
                                    </a>

                                    @if ($theme->status->value !== \App\Enums\ThemeStatus::Published->value)
                                        <form method="POST" action="{{ route('admin.themes.publish', $theme) }}" onsubmit="return confirm('Publish this theme?');">
                                            @csrf
                                            <button type="submit" class="rounded-md bg-zinc-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-zinc-700">
                                                Publish
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-zinc-500">No themes found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
