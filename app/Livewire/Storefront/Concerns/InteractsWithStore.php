<?php

namespace App\Livewire\Storefront\Concerns;

use App\Enums\ThemeStatus;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\NavigationMenu;
use App\Models\Store;
use App\Services\CartService;
use App\Services\NavigationService;
use Illuminate\Support\Facades\Auth;

/**
 * Shared helpers for storefront Livewire components.
 */
trait InteractsWithStore
{
    protected function store(): Store
    {
        return app('current_store');
    }

    protected function customer(): ?Customer
    {
        return Auth::guard('customer')->user();
    }

    protected function sessionCart(): Cart
    {
        return app(CartService::class)->getOrCreateForSession($this->store(), $this->customer());
    }

    /**
     * @return array<string, mixed>
     */
    protected function themeSettings(): array
    {
        $store = $this->store();
        $theme = $store->themes()->where('status', ThemeStatus::Published->value)->first();
        $settings = $theme?->settings?->settings_json ?? [];

        return array_replace($this->defaultThemeSettings(), $settings);
    }

    public function themeSetting(string $key, mixed $default = null): mixed
    {
        return $this->themeSettings()[$key] ?? $default;
    }

    /**
     * @return list<array{label: string, url: string, type: string}>
     */
    protected function navigation(string $handle): array
    {
        $menu = NavigationMenu::where('store_id', $this->store()->id)
            ->where('handle', $handle)
            ->first();

        if (! $menu) {
            return [];
        }

        return app(NavigationService::class)->buildTree($menu);
    }

    /**
     * Format an amount in minor units (cents) per the storefront convention.
     * Example: 2499 -> "24.99 EUR".
     */
    public function money(int $amount, ?string $currency = null): string
    {
        $currency ??= $this->store()->default_currency;

        return number_format($amount / 100, 2, '.', ',').' '.$currency;
    }

    /**
     * Map an order status to a storefront badge variant.
     */
    public function statusBadgeVariant(string $status): string
    {
        return match ($status) {
            'pending' => 'pending',
            'paid' => 'success',
            'fulfilled' => 'info',
            'refunded' => 'danger',
            default => 'muted',
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultThemeSettings(): array
    {
        return [
            'primary_color' => '#2563eb',
            'secondary_color' => '#4f46e5',
            'accent_color' => '#0ea5e9',
            'dark_mode' => 'system',
            'sticky_header' => true,
            'show_announcement_bar' => false,
            'announcement_text' => '',
            'announcement_link' => null,
            'announcement_bg_color' => '#111827',
            'logo_url' => null,
            'footer_text' => null,
            'footer_columns' => 4,
            'contact_email' => null,
            'store_address' => null,
            'social_facebook' => null,
            'social_instagram' => null,
            'social_twitter' => null,
            'social_tiktok' => null,
            'social_youtube' => null,
            'payment_icons' => true,
            'hero_heading' => null,
            'hero_subheading' => null,
            'hero_cta_text' => null,
            'hero_cta_link' => null,
            'hero_image' => null,
            'featured_collection_handles' => [],
            'featured_products_count' => 8,
            'featured_products_collection' => null,
            'home_sections' => ['hero', 'featured-collections', 'featured-products', 'newsletter', 'rich-text'],
            'rich_text_html' => null,
            'newsletter_enabled' => true,
            'products_per_page' => 12,
            'show_vendor' => true,
            'show_quantity_selector' => true,
            'meta_description' => '',
        ];
    }
}
