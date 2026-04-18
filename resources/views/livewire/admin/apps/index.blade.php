<div class="space-y-4">
    <flux:heading size="xl">Apps</flux:heading>
    <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left text-zinc-500 dark:bg-zinc-800">
                <tr>
                    <th class="p-3">App</th>
                    <th class="p-3">Status</th>
                    <th class="p-3">Installed at</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($installations as $installation)
                    <tr wire:key="install-{{ $installation->id }}" class="border-t border-zinc-100 dark:border-zinc-800">
                        <td class="p-3"><a class="text-sky-600 hover:underline" href="{{ route('admin.apps.show', $installation) }}" wire:navigate>{{ $installation->app?->name ?? $installation->id }}</a></td>
                        <td class="p-3">{{ $installation->status?->value ?? $installation->status }}</td>
                        <td class="p-3">{{ $installation->created_at?->format('Y-m-d') }}</td>
                    </tr>
                @empty
                    <tr><td class="p-4 text-zinc-500" colspan="3">No apps installed.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
