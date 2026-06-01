<div class="flex flex-col gap-6">
    <flux:heading size="lg">{{ __('Media') }}</flux:heading>

    <form wire:submit="save" class="flex flex-col gap-4">
        <input type="file" wire:model="upload" accept="image/*" data-test="media-upload" />

        @error('upload')
            <flux:text variant="danger" data-test="media-upload-error">{{ $message }}</flux:text>
        @enderror

        <div>
            <flux:button variant="primary" type="submit" data-test="media-save">
                {{ __('Upload') }}
            </flux:button>
        </div>
    </form>

    <ul class="grid grid-cols-2 gap-4 sm:grid-cols-4" data-test="media-list">
        @foreach ($media as $item)
            <li class="flex flex-col gap-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700" wire:key="media-{{ $item->id }}">
                <img
                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($item->storage_key) }}"
                    alt="{{ $item->alt_text }}"
                    class="aspect-square w-full rounded object-cover"
                />

                <flux:badge size="sm">{{ $item->status->value }}</flux:badge>

                <flux:input
                    wire:change="updateAltText({{ $item->id }}, $event.target.value)"
                    value="{{ $item->alt_text }}"
                    :placeholder="__('Alt text')"
                    size="sm"
                />

                <flux:button
                    variant="danger"
                    size="sm"
                    wire:click="delete({{ $item->id }})"
                    data-test="media-delete-{{ $item->id }}"
                >
                    {{ __('Delete') }}
                </flux:button>
            </li>
        @endforeach
    </ul>
</div>
