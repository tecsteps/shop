<?php

namespace App\Livewire\Admin\Pages;

use App\Enums\PageStatus;
use App\Livewire\Admin\Concerns\SendsToasts;
use App\Models\Page;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Shared content page form used by both the create and edit routes (roadmap
 * step 7.5 shared-form pattern).
 */
#[Layout('layouts::admin')]
class Form extends Component
{
    use AuthorizesRequests, SendsToasts;

    public ?Page $page = null;

    public string $title = '';

    public string $handle = '';

    public string $bodyHtml = '';

    public string $status = 'draft';

    public ?string $publishedAt = null;

    public function mount(?int $pageId = null): void
    {
        if ($pageId !== null) {
            $this->page = Page::query()->findOrFail($pageId);

            $this->authorize('view', $this->page);
            $this->fillFromPage();

            return;
        }

        $this->authorize('create', Page::class);
    }

    public function save(): void
    {
        if ($this->isEditing) {
            $this->authorize('update', $this->page);
        } else {
            $this->authorize('create', Page::class);
        }

        $this->validate();

        $publishedAt = filled($this->publishedAt) ? Carbon::parse($this->publishedAt) : null;

        if ($this->status === PageStatus::Published->value && $publishedAt === null) {
            $publishedAt = now();
        }

        $attributes = [
            'title' => $this->title,
            'handle' => $this->resolvedHandle(),
            'body_html' => $this->bodyHtml !== '' ? $this->bodyHtml : null,
            'status' => $this->status,
            'published_at' => $publishedAt,
        ];

        if ($this->isEditing) {
            $this->page->update($attributes);
            $this->page->refresh();
            $this->fillFromPage();
            $this->toast(__('Page saved'));

            return;
        }

        $this->page = Page::query()->create($attributes);

        $this->flashToast(__('Page saved'));
        $this->redirect(route('admin.pages.edit', $this->page), navigate: true);
    }

    public function deletePage(): void
    {
        $this->authorize('delete', $this->page);

        $this->page->delete();

        $this->flashToast(__('Page deleted.'));
        $this->redirect(route('admin.pages.index'), navigate: true);
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->page !== null;
    }

    public function render(): View
    {
        return view('livewire.admin.pages.form')
            ->title($this->isEditing ? $this->page->title : __('Add page'));
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'handle' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('pages', 'handle')
                    ->where('store_id', app('current_store')->getKey())
                    ->ignore($this->page?->getKey()),
            ],
            'bodyHtml' => ['nullable', 'string', 'max:16777215'],
            'status' => ['required', 'in:draft,published,archived'],
            'publishedAt' => ['nullable', 'date'],
        ];
    }

    protected function resolvedHandle(): string
    {
        $handle = trim($this->handle) !== '' ? trim($this->handle) : $this->title;

        return Str::slug($handle);
    }

    protected function fillFromPage(): void
    {
        $this->title = $this->page->title;
        $this->handle = $this->page->handle;
        $this->bodyHtml = (string) $this->page->body_html;
        $this->status = $this->page->status->value;
        $this->publishedAt = $this->page->published_at?->format('Y-m-d\TH:i');
    }
}
