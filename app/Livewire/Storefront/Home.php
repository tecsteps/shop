<?php

namespace App\Livewire\Storefront;

use App\Models\Collection;
use App\Models\Product;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class Home extends Component
{
    public string $email = '';

    public function subscribe(): void
    {
        $key = 'newsletter:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('email', 'Please wait before trying again.');

            return;
        }

        RateLimiter::hit($key, 60);
        $this->validate(['email' => ['required', 'email', 'max:255']]);
        $now = now();

        DB::table('newsletter_subscriptions')->upsert([[
            'store_id' => app('current_store')->id,
            'email' => mb_strtolower(trim($this->email)),
            'source' => 'storefront',
            'subscribed_at' => $now,
            'unsubscribed_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['store_id', 'email'], ['subscribed_at', 'unsubscribed_at', 'updated_at']);

        if ($customer = auth('customer')->user()) {
            $customer->forceFill(['marketing_opt_in' => true])->save();
        }

        $this->reset('email');
        RateLimiter::clear($key);
        session()->flash('newsletter-success', 'Thanks for subscribing.');
    }

    public function render(): mixed
    {
        $settings = app()->bound('current_store') ? app('current_store')->theme_settings : [];

        return view('storefront.home', [
            'collections' => Collection::query()->where('status', 'active')->withCount('products')->orderBy('title')->get(),
            'featuredProducts' => Product::query()->where('status', 'active')->whereNotNull('published_at')->with(['variants.inventoryItem', 'media'])->latest('published_at')->limit(8)->get(),
            'settings' => $settings,
            'safeRichText' => app(HtmlSanitizer::class)->sanitize(data_get($settings, 'home.rich_text.html')),
        ])->layout('storefront.layouts.app');
    }
}
