<form wire:submit="save" class="space-y-6 pb-20">
    <x-admin.page-header :title="$page ? $title : 'Create page'" :description="$page ? 'Edit storefront page content and publishing.' : 'Add a new informational page to your storefront.'"><x-slot:actions>@if($page)<flux:button type="button" wire:click="deletePage" wire:confirm="Delete this page?" variant="danger" icon="trash">Delete page</flux:button>@endif</x-slot:actions></x-admin.page-header>
    <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(18rem,1fr)]">
        <x-admin.card title="Content"><div class="space-y-5"><flux:input wire:model.live.debounce.300ms="title" label="Title" required /><flux:input wire:model="handle" label="Handle" description="Used in the storefront URL." required /><flux:textarea wire:model="bodyHtml" label="Body" rows="16" /></div></x-admin.card>
        <x-admin.card title="Publishing"><div class="space-y-5"><flux:select wire:model="status" label="Status"><flux:select.option value="draft">Draft</flux:select.option><flux:select.option value="published">Published</flux:select.option><flux:select.option value="archived">Archived</flux:select.option></flux:select><flux:input wire:model="publishedAt" type="datetime-local" label="Published at" /></div></x-admin.card>
    </div>
    <x-admin.sticky-save-bar :discard-url="url('/admin/pages')" :dirty-only="false" />
</form>
