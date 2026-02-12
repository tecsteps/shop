@extends('admin.layout')

@section('title', 'Apps')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-zinc-900">Apps</h1>
        <p class="mt-1 text-sm text-zinc-600">Installed app overview for the current store.</p>
    </div>

    @if (! $sourceAvailable)
        <div class="rounded-lg border border-zinc-200 bg-white p-5 text-sm text-zinc-600">
            No app installation schema detected yet. This page is ready and will show data when the apps phase tables are available.
        </div>
    @else
        <div class="mb-4 rounded-lg border border-zinc-200 bg-zinc-100 px-4 py-3 text-xs text-zinc-600">
            Data source: <code>{{ $sourceTable }}</code>
        </div>

        <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm">
                    <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500">
                        <tr>
                            <th class="px-4 py-3">App</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Installed</th>
                            <th class="px-4 py-3">Scopes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 bg-white">
                        @forelse ($installedApps as $app)
                            <tr>
                                <td class="px-4 py-3 font-medium text-zinc-900">{{ $app->name }}</td>
                                <td class="px-4 py-3">{{ $app->status }}</td>
                                <td class="px-4 py-3 text-zinc-600">{{ $app->installed_at ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    @if ($app->scopes !== [])
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($app->scopes as $scope)
                                                <span class="rounded-md border border-zinc-300 bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700">{{ $scope }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-zinc-500">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-10 text-center text-zinc-500">No installed apps found for this store.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
