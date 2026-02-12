<?php

namespace App\Http\Controllers\Admin;

use App\Models\StoreSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SearchSettingsController extends AdminController
{
    public function edit(): View
    {
        $search = $this->readSearchSettings();

        return view('admin.search.settings', [
            'synonymsText' => $this->linesToText($search['synonyms'] ?? []),
            'stopWordsText' => $this->linesToText($search['stop_words'] ?? []),
            'searchServiceAvailable' => class_exists(\App\Services\SearchService::class),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'synonyms' => ['nullable', 'string'],
            'stop_words' => ['nullable', 'string'],
        ]);

        $this->writeSearchSettings(
            $this->textToLines($validated['synonyms'] ?? ''),
            $this->textToLines($validated['stop_words'] ?? ''),
        );

        return redirect()->route('admin.search.settings.edit')
            ->with('status', 'Search settings saved.');
    }

    public function reindex(): RedirectResponse
    {
        try {
            $message = $this->triggerReindex();
        } catch (\Throwable $exception) {
            return back()->withErrors([
                'reindex' => sprintf('Reindex failed: %s', $exception->getMessage()),
            ]);
        }

        return redirect()->route('admin.search.settings.edit')
            ->with('status', $message);
    }

    protected function storeSettings(): StoreSettings
    {
        return StoreSettings::query()->firstOrCreate(
            ['store_id' => $this->currentStore()->id],
            ['settings_json' => []],
        );
    }

    /**
     * @return array{synonyms: mixed, stop_words: mixed}
     */
    protected function readSearchSettings(): array
    {
        $searchSettingClass = \App\Models\SearchSetting::class;

        if (class_exists($searchSettingClass) && Schema::hasTable('search_settings')) {
            /** @var object{synonyms_json:mixed, stop_words_json:mixed} $searchSetting */
            $searchSetting = $searchSettingClass::query()->firstOrCreate(
                ['store_id' => $this->currentStore()->id],
                ['synonyms_json' => [], 'stop_words_json' => [], 'updated_at' => now()],
            );

            return [
                'synonyms' => $searchSetting->synonyms_json ?? [],
                'stop_words' => $searchSetting->stop_words_json ?? [],
            ];
        }

        $settings = $this->storeSettings();
        $search = (array) (($settings->settings_json ?? [])['search'] ?? []);

        return [
            'synonyms' => $search['synonyms'] ?? [],
            'stop_words' => $search['stop_words'] ?? [],
        ];
    }

    /**
     * @param  list<string>  $synonyms
     * @param  list<string>  $stopWords
     */
    protected function writeSearchSettings(array $synonyms, array $stopWords): void
    {
        $searchSettingClass = \App\Models\SearchSetting::class;

        if (class_exists($searchSettingClass) && Schema::hasTable('search_settings')) {
            $searchSettingClass::query()->updateOrCreate(
                ['store_id' => $this->currentStore()->id],
                [
                    'synonyms_json' => $synonyms,
                    'stop_words_json' => $stopWords,
                    'updated_at' => now(),
                ],
            );

            return;
        }

        $settings = $this->storeSettings();
        $json = $settings->settings_json ?? [];
        $search = (array) ($json['search'] ?? []);
        $search['synonyms'] = $synonyms;
        $search['stop_words'] = $stopWords;
        $json['search'] = $search;

        $settings->update([
            'settings_json' => $json,
        ]);
    }

    protected function triggerReindex(): string
    {
        $searchServiceClass = \App\Services\SearchService::class;

        if (! class_exists($searchServiceClass)) {
            return 'SearchService is not available in this build. Settings were saved only.';
        }

        $service = app($searchServiceClass);

        foreach (['reindexStore', 'reindex', 'rebuildIndex', 'syncAllProducts'] as $method) {
            if (! method_exists($service, $method)) {
                continue;
            }

            if ($this->invokeReindexMethod($service, $method)) {
                return 'Search reindex triggered.';
            }
        }

        return 'SearchService is available, but no supported reindex method was found.';
    }

    protected function invokeReindexMethod(object $service, string $method): bool
    {
        $store = $this->currentStore();

        $attempts = [
            [$store],
            [$store->id],
            [],
        ];

        foreach ($attempts as $args) {
            try {
                $service->{$method}(...$args);

                return true;
            } catch (\ArgumentCountError|\TypeError) {
                continue;
            }
        }

        return false;
    }

    protected function linesToText(mixed $lines): string
    {
        if (! is_array($lines)) {
            return '';
        }

        $normalized = array_map(function ($line): string {
            if (is_array($line)) {
                $parts = array_map(static fn ($item): string => trim((string) $item), $line);

                return implode(', ', array_values(array_filter($parts, static fn (string $item): bool => $item !== '')));
            }

            return trim((string) $line);
        }, $lines);

        $normalized = array_values(array_filter($normalized, static fn (string $line): bool => $line !== ''));

        return implode("\n", $normalized);
    }

    /**
     * @return list<string>
     */
    protected function textToLines(string $input): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $input) ?: [];
        $lines = array_map(static fn (string $line): string => trim($line), $lines);
        $lines = array_values(array_filter($lines, static fn (string $line): bool => $line !== ''));

        return array_values(array_unique($lines));
    }
}
