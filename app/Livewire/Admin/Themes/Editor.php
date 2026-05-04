<?php

namespace App\Livewire\Admin\Themes;

use App\Enums\ThemeStatus;
use App\Models\Store;
use App\Models\Theme;
use App\Models\ThemeFile;
use App\Models\ThemeSettings;
use App\Services\ThemeSettingsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class Editor extends Component
{
    use AuthorizesRequests;

    public Theme $theme;

    public string $selectedSection = 'announcement';

    public ?int $selectedFileId = null;

    public string $fileContents = '';

    /**
     * @var array<string, mixed>
     */
    public array $settings = [];

    public function mount(Theme $theme, ThemeSettingsService $themeSettings): void
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);
        abort_unless((int) $theme->store_id === $store->getKey(), 404);

        $this->authorize('update', $theme);

        $this->theme = $theme;
        $this->settings = array_replace_recursive(
            $themeSettings->defaultsForStore($store),
            $theme->settings?->settings_json ?? [],
        );
    }

    public function selectSection(string $sectionKey): void
    {
        abort_unless(array_key_exists($sectionKey, $this->sections()), 404);

        $this->selectedSection = $sectionKey;
        $this->selectedFileId = null;
        $this->fileContents = '';
    }

    public function selectFile(int $fileId): void
    {
        $file = $this->themeFile($fileId);

        $this->authorize('update', $this->theme);

        $this->selectedFileId = $file->getKey();
        $this->fileContents = Storage::disk('local')->exists($file->storage_key)
            ? Storage::disk('local')->get($file->storage_key)
            : '';
    }

    public function saveFile(): void
    {
        abort_unless($this->selectedFileId !== null, 404);

        $this->authorize('update', $this->theme);

        $this->validate([
            'fileContents' => ['string', 'max:262144'],
        ]);

        $file = $this->themeFile($this->selectedFileId);
        Storage::disk('local')->put($file->storage_key, $this->fileContents);

        $file->forceFill([
            'sha256' => hash('sha256', $this->fileContents),
            'byte_size' => strlen($this->fileContents),
        ])->save();

        session()->flash('status', 'Theme file saved');
        $this->dispatch('toast', type: 'success', message: __('Theme file saved'));
    }

    public function save(ThemeSettingsService $settings): void
    {
        $this->authorize('update', $this->theme);

        ThemeSettings::withoutGlobalScopes()->updateOrCreate(
            ['theme_id' => $this->theme->getKey()],
            [
                'settings_json' => $this->settings,
                'updated_at' => now(),
            ],
        );

        $settings->forget($this->store());

        session()->flash('status', 'Theme saved');
        $this->dispatch('toast', type: 'success', message: __('Theme saved'));
    }

    public function publish(ThemeSettingsService $settings): void
    {
        $this->authorize('publish', $this->theme);

        DB::transaction(function (): void {
            Theme::withoutGlobalScopes()
                ->where('store_id', $this->theme->store_id)
                ->update([
                    'status' => ThemeStatus::Draft,
                    'published_at' => null,
                ]);

            $this->theme->forceFill([
                'status' => ThemeStatus::Published,
                'published_at' => now(),
            ])->save();
        });

        $this->save($settings);

        session()->flash('status', 'Theme saved and published');
    }

    public function refreshPreview(): void
    {
        $this->dispatch('theme-preview-refresh');
    }

    /**
     * @return array<string, array{label: string, fields: array<int, array{key: string, label: string, type: string, options?: array<string, string>}>}>
     */
    public function sections(): array
    {
        return [
            'announcement' => [
                'label' => 'Announcement',
                'fields' => [
                    ['key' => 'announcement.enabled', 'label' => 'Enabled', 'type' => 'checkbox'],
                    ['key' => 'announcement.text', 'label' => 'Text', 'type' => 'text'],
                    ['key' => 'announcement.url', 'label' => 'URL', 'type' => 'text'],
                ],
            ],
            'header' => [
                'label' => 'Header',
                'fields' => [
                    ['key' => 'header.sticky', 'label' => 'Sticky header', 'type' => 'checkbox'],
                    ['key' => 'header.main_menu', 'label' => 'Main menu handle', 'type' => 'text'],
                ],
            ],
            'home' => [
                'label' => 'Home hero',
                'fields' => [
                    ['key' => 'home.hero.eyebrow', 'label' => 'Eyebrow', 'type' => 'text'],
                    ['key' => 'home.hero.heading', 'label' => 'Heading', 'type' => 'text'],
                    ['key' => 'home.hero.subheading', 'label' => 'Subheading', 'type' => 'textarea'],
                    ['key' => 'home.hero.primary_label', 'label' => 'Primary button label', 'type' => 'text'],
                    ['key' => 'home.hero.primary_url', 'label' => 'Primary button URL', 'type' => 'text'],
                    ['key' => 'home.featured_product_limit', 'label' => 'Featured products', 'type' => 'number'],
                    ['key' => 'home.featured_collection_limit', 'label' => 'Featured collections', 'type' => 'number'],
                ],
            ],
            'footer' => [
                'label' => 'Footer',
                'fields' => [
                    ['key' => 'footer.menu', 'label' => 'Menu handle', 'type' => 'text'],
                    ['key' => 'footer.tagline', 'label' => 'Tagline', 'type' => 'textarea'],
                ],
            ],
        ];
    }

    public function previewUrl(): string
    {
        return route('home');
    }

    public function render(): mixed
    {
        return view('livewire.admin.themes.editor', [
            'sections' => $this->sections(),
            'activeSection' => $this->sections()[$this->selectedSection],
            'themeFiles' => $this->themeFiles(),
            'selectedFile' => $this->selectedFile(),
            'previewUrl' => $this->previewUrl(),
        ])->layout('layouts.app', [
            'title' => __('Theme editor'),
        ]);
    }

    private function store(): Store
    {
        return Store::query()->whereKey($this->theme->store_id)->firstOrFail();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, ThemeFile>
     */
    private function themeFiles(): \Illuminate\Database\Eloquent\Collection
    {
        return ThemeFile::withoutGlobalScopes()
            ->where('theme_id', $this->theme->getKey())
            ->orderBy('path')
            ->get();
    }

    private function selectedFile(): ?ThemeFile
    {
        if ($this->selectedFileId === null) {
            return null;
        }

        return $this->themeFile($this->selectedFileId);
    }

    private function themeFile(int $fileId): ThemeFile
    {
        return ThemeFile::withoutGlobalScopes()
            ->where('theme_id', $this->theme->getKey())
            ->whereKey($fileId)
            ->firstOrFail();
    }
}
