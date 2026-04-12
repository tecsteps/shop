<?php

use App\Livewire\Storefront\Pages\Show;
use App\Models\Page;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('renders a published page body', function (): void {
    Page::factory()->for($this->store)->create([
        'title' => 'About Us',
        'handle' => 'about',
        'body_html' => '<p>We make good things.</p>',
    ]);

    Livewire::test(Show::class, ['handle' => 'about'])
        ->assertStatus(200)
        ->assertSee('About Us')
        ->assertSee('We make good things.', false);
});

it('fails for a draft page', function (): void {
    Page::factory()->for($this->store)->draft()->create(['handle' => 'hidden']);

    Livewire::test(Show::class, ['handle' => 'hidden']);
})->throws(Illuminate\Database\Eloquent\ModelNotFoundException::class);
