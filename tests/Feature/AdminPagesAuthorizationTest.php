<?php

use App\Livewire\Admin\Pages\Form as PageForm;
use App\Livewire\Admin\Pages\Index as PageIndex;
use App\Models\Page;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

it('allows staff to create and edit pages but blocks page deletion', function () {
    $store = shopStore();
    $staff = User::factory()->create();
    $staff->stores()->attach($store->id, ['role' => 'staff']);
    app()->instance('current_store', $store);

    Livewire::actingAs($staff)->test(PageIndex::class)->assertOk();

    Livewire::actingAs($staff)->test(PageForm::class)
        ->set('title', 'Staff-created page')
        ->set('bodyHtml', '<p>Page content</p>')
        ->set('status', 'published')
        ->call('save')
        ->assertHasNoErrors();

    $page = Page::query()->where('title', 'Staff-created page')->firstOrFail();
    Livewire::actingAs($staff)->test(PageForm::class, ['page' => $page->id])
        ->set('title', 'Staff-updated page')
        ->call('save')
        ->assertHasNoErrors();

    expect($page->fresh()->title)->toBe('Staff-updated page')
        ->and(Gate::forUser($staff)->allows('delete', $page->fresh()))->toBeFalse();

    Livewire::actingAs($staff)->test(PageIndex::class)
        ->call('delete', $page->id)
        ->assertForbidden();
});
