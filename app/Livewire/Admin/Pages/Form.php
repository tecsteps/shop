<?php

namespace App\Livewire\Admin\Pages;

use App\Enums\PageStatus;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Store;
use App\Services\NavigationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;

    public ?Page $page = null;

    public string $title = '';

    public string $handle = '';

    public string $bodyHtml = '';

    public string $status = 'draft';

    public string $publishedAt = '';

    public string $actionMessage = '';

    public function mount(?Page $page = null): void
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        if ($page?->exists) {
            abort_unless((int) $page->store_id === $store->getKey(), 404);

            $this->authorize('update', $page);

            $this->page = $page;
            $this->fillFromPage($page);

            return;
        }

        $this->authorize('create', Page::class);
    }

    public function updatedTitle(string $title): void
    {
        if ($this->page instanceof Page || $this->handle !== '') {
            return;
        }

        $this->handle = Str::slug($title);
    }

    public function save(NavigationService $navigation): void
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        $this->authorizeSave();

        $this->handle = Str::slug($this->handle);

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('pages', 'handle')
                    ->where('store_id', $store->getKey())
                    ->ignore($this->page?->getKey()),
            ],
            'bodyHtml' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_column(PageStatus::cases(), 'value'))],
            'publishedAt' => ['nullable', 'date'],
        ], [], [
            'bodyHtml' => 'body',
            'publishedAt' => 'published at',
        ]);

        $publishedAt = $this->publishedAt !== '' ? $this->publishedAt : null;

        if ($this->status === PageStatus::Published->value && $publishedAt === null) {
            $publishedAt = now();
        }

        $page = $this->page instanceof Page
            ? tap($this->page)->update($this->payload($store, $publishedAt))
            : Page::withoutGlobalScopes()->create($this->payload($store, $publishedAt));

        $this->page = $page->refresh();
        $this->fillFromPage($this->page);
        $this->forgetNavigation($store, $navigation);

        $this->actionMessage = 'Page saved';

        session()->flash('status', 'Page saved');
        $this->dispatch('toast', type: 'success', message: __('Page saved'));
    }

    public function deletePage(NavigationService $navigation): void
    {
        abort_unless($this->page instanceof Page, 404);

        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        $this->authorize('delete', $this->page);

        $this->page->delete();
        $this->forgetNavigation($store, $navigation);

        $this->redirectRoute('admin.pages.index', navigate: true);
    }

    public function render(): mixed
    {
        return view('livewire.admin.pages.form', [
            'isEditing' => $this->page instanceof Page,
        ])->layout('layouts.app', [
            'title' => $this->page ? __('Edit page') : __('Create page'),
        ]);
    }

    private function fillFromPage(Page $page): void
    {
        $this->title = $page->title;
        $this->handle = $page->handle;
        $this->bodyHtml = (string) $page->body_html;
        $this->status = $page->status->value;
        $this->publishedAt = $page->published_at?->format('Y-m-d\TH:i') ?? '';
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Store $store, mixed $publishedAt): array
    {
        return [
            'store_id' => $store->getKey(),
            'title' => $this->title,
            'handle' => $this->handle,
            'body_html' => $this->bodyHtml,
            'status' => PageStatus::from($this->status),
            'published_at' => $publishedAt,
        ];
    }

    private function authorizeSave(): void
    {
        if ($this->page instanceof Page) {
            $this->authorize('update', $this->page);

            return;
        }

        $this->authorize('create', Page::class);
    }

    private function forgetNavigation(Store $store, NavigationService $navigation): void
    {
        NavigationMenu::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->get()
            ->each(fn (NavigationMenu $menu): mixed => $navigation->forget($menu));
    }
}
