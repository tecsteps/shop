<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PageController extends AdminController
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));
        $status = (string) $request->string('status', '');

        $pages = Page::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('handle', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, $this->enumValues(PageStatus::class), true), function ($query) use ($status): void {
                $query->where('status', $status);
            })
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.pages.index', [
            'pages' => $pages,
            'search' => $search,
            'status' => $status,
            'statuses' => PageStatus::cases(),
        ]);
    }

    public function create(): View
    {
        return view('admin.pages.create', [
            'statuses' => PageStatus::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePage($request);

        $handle = $this->uniqueHandle(
            $validated['handle'] ?: $validated['title'],
            fn (string $candidate): bool => Page::query()->where('handle', $candidate)->exists(),
            'page'
        );

        Page::query()->create([
            'title' => $validated['title'],
            'handle' => $handle,
            'body_html' => $validated['body_html'],
            'status' => $validated['status'],
            'published_at' => $validated['status'] === PageStatus::Published->value ? now() : null,
        ]);

        return redirect()->route('admin.pages.index')
            ->with('status', 'Page created.');
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.edit', [
            'page' => $page,
            'statuses' => PageStatus::cases(),
        ]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $validated = $this->validatePage($request);

        $handle = $this->uniqueHandle(
            $validated['handle'] ?: $validated['title'],
            fn (string $candidate): bool => Page::query()
                ->where('handle', $candidate)
                ->whereKeyNot($page->id)
                ->exists(),
            'page'
        );

        $page->update([
            'title' => $validated['title'],
            'handle' => $handle,
            'body_html' => $validated['body_html'],
            'status' => $validated['status'],
            'published_at' => $validated['status'] === PageStatus::Published->value
                ? ($page->published_at ?? now())
                : null,
        ]);

        return redirect()->route('admin.pages.edit', $page)
            ->with('status', 'Page updated.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return redirect()->route('admin.pages.index')
            ->with('status', 'Page deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatePage(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'body_html' => ['nullable', 'string'],
            'status' => ['required', Rule::in($this->enumValues(PageStatus::class))],
        ]);
    }
}
