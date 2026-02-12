@extends('admin.layout')

@section('title', 'Edit Theme')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900">Theme Settings</h1>
            <p class="mt-1 text-sm text-zinc-600">{{ $theme->name }} {{ $theme->version ? '· '.$theme->version : '' }}</p>
        </div>

        <a href="{{ route('admin.themes.index') }}" class="rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100">
            Back to Themes
        </a>
    </div>

    <form id="theme-settings-form" method="POST" action="{{ route('admin.themes.update', $theme) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-lg border border-zinc-200 bg-white p-5">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Settings JSON</h2>

            <div class="mt-4">
                <textarea
                    name="settings_json"
                    rows="22"
                    class="w-full rounded-md border border-zinc-300 px-3 py-2 font-mono text-sm"
                >{{ old('settings_json', $settingsJson) }}</textarea>
                <p class="mt-2 text-xs text-zinc-500">Edit raw theme configuration JSON. Invalid JSON will be rejected.</p>
            </div>
        </section>

        <button type="submit" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700">
            Save Theme Settings
        </button>
    </form>

    @if ($theme->status->value !== \App\Enums\ThemeStatus::Published->value)
        <form method="POST" action="{{ route('admin.themes.publish', $theme) }}" class="mt-3" onsubmit="return confirm('Publish this theme?');">
            @csrf
            <button type="submit" class="rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100">
                Publish Theme
            </button>
        </form>
    @endif
@endsection
