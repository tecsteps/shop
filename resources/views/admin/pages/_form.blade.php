<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <section class="rounded-lg border border-zinc-200 bg-white p-5">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Content</h2>

            <div class="mt-4 grid gap-4">
                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Title</span>
                    <input type="text" name="title" value="{{ old('title', $page->title ?? '') }}" required class="w-full rounded-md border border-zinc-300 px-3 py-2">
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Handle</span>
                    <input type="text" name="handle" value="{{ old('handle', $page->handle ?? '') }}" placeholder="auto-from-title" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Body (HTML allowed)</span>
                    <textarea name="body_html" rows="14" class="w-full rounded-md border border-zinc-300 px-3 py-2">{{ old('body_html', $page->body_html ?? '') }}</textarea>
                </label>
            </div>
        </section>
    </div>

    <div class="space-y-6">
        <section class="rounded-lg border border-zinc-200 bg-white p-5">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Status</h2>

            <div class="mt-3">
                <select name="status" class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
                    @foreach ($statuses as $statusOption)
                        <option
                            value="{{ $statusOption->value }}"
                            @selected(old('status', $page?->status?->value ?? \App\Enums\PageStatus::Draft->value) === $statusOption->value)
                        >
                            {{ ucfirst($statusOption->value) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <p class="mt-3 text-xs text-zinc-500">Published pages are visible on the storefront at <code>/pages/{handle}</code>.</p>
        </section>
    </div>
</div>
