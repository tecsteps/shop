<div class="p-6 space-y-4 max-w-2xl">
    <h1 class="text-2xl font-semibold tracking-tight">
        {{ $subscriptionId ? 'Edit subscription' : 'New webhook subscription' }}
    </h1>

    <form wire:submit.prevent="save" class="flex flex-col gap-4">
        <div>
            <label class="mb-1 block text-sm font-medium">Event</label>
            <select wire:model="event_type" class="w-full rounded border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
                @foreach ($topics as $topic)
                    <option value="{{ $topic->value }}">{{ $topic->value }}</option>
                @endforeach
            </select>
            @error('event_type') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">Target URL</label>
            <input type="url" wire:model="target_url" class="w-full rounded border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900" />
            @error('target_url') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">Signing secret</label>
            <input type="text" wire:model="signing_secret" class="w-full rounded border border-neutral-300 px-3 py-2 font-mono text-xs dark:border-neutral-700 dark:bg-neutral-900" />
            @error('signing_secret') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">Status</label>
            <select wire:model="status" class="w-full rounded border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
                <option value="active">active</option>
                <option value="paused">paused</option>
                <option value="disabled">disabled</option>
            </select>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="rounded-full bg-neutral-900 px-6 py-2 text-sm font-semibold text-white hover:bg-neutral-700">Save</button>
            <a href="{{ url('/admin/settings/webhooks') }}" class="rounded-full border border-neutral-300 px-6 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-900">Cancel</a>
        </div>
    </form>
</div>
