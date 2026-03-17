<?php

namespace App\Livewire\Admin\Themes;

use App\Models\Theme;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    public function getThemesProperty()
    {
        return Theme::withoutGlobalScopes()
            ->where('store_id', session('store_id'))
            ->orderByDesc('is_published')
            ->orderByDesc('updated_at')
            ->get();
    }

    public function publishTheme(int $themeId): void
    {
        Theme::withoutGlobalScopes()
            ->where('store_id', session('store_id'))
            ->update(['is_published' => false]);

        Theme::withoutGlobalScopes()->findOrFail($themeId)
            ->update(['is_published' => true]);

        $this->dispatch('toast', type: 'success', message: 'Theme published.');
    }

    public function duplicateTheme(int $themeId): void
    {
        $theme = Theme::withoutGlobalScopes()->findOrFail($themeId);

        $newTheme = $theme->replicate();
        $newTheme->name = $theme->name.' (Copy)';
        $newTheme->is_published = false;
        $newTheme->save();

        $this->dispatch('toast', type: 'success', message: 'Theme duplicated.');
    }

    public function deleteTheme(int $themeId): void
    {
        $theme = Theme::withoutGlobalScopes()->findOrFail($themeId);
        if ($theme->is_published) {
            $this->dispatch('toast', type: 'error', message: 'Cannot delete the published theme.');

            return;
        }
        $theme->delete();
        $this->dispatch('toast', type: 'success', message: 'Theme deleted.');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.themes.index');
    }
}
