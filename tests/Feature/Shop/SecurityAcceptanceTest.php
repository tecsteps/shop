<?php

use App\Models\Page;
use App\Models\Product;
use App\Models\Theme;

describe('persisted rich text security', function () {
    it('sanitizes product and page HTML at the model persistence boundary', function () {
        $context = createStoreContext();
        $malicious = <<<'HTML'
<p class="lead" onclick="alert(1)">Safe <strong>copy</strong></p>
<script>window.stolen = document.cookie</script>
<a href="javascript&#58;alert(1)" style="color:red">Unsafe link</a>
<a href="https://example.test/docs" target="_blank" rel="opener">Safe link</a>
<img src="data:image/svg+xml,evil" onerror="alert(1)" alt="bad">
<img src="https://cdn.example.test/photo.jpg" alt="Safe photo" width="999">
<iframe src="https://evil.example"></iframe>
HTML;

        $product = Product::factory()->for($context['store'])->create([
            'description_html' => $malicious,
        ]);
        $page = Page::factory()->for($context['store'])->create([
            'body_html' => $malicious,
        ]);

        foreach ([$product->getRawOriginal('description_html'), $page->getRawOriginal('body_html')] as $persisted) {
            expect($persisted)
                ->toContain('<p>Safe <strong>copy</strong></p>')
                ->toContain('<a href="https://example.test/docs">Safe link</a>')
                ->toContain('<img src="https://cdn.example.test/photo.jpg" alt="Safe photo">')
                ->not->toContain('script')
                ->not->toContain('iframe')
                ->not->toContain('javascript')
                ->not->toContain('data:image')
                ->not->toContain('onclick')
                ->not->toContain('onerror')
                ->not->toContain('style=')
                ->not->toContain('target=')
                ->not->toContain('rel=')
                ->not->toContain('width=');
        }

        $product->update(['description_html' => '<h2>Updated</h2><svg onload="alert(1)"><circle /></svg>']);
        $page->update(['body_html' => '<blockquote cite="bad">Quoted</blockquote><object data="bad"></object>']);

        expect($product->refresh()->getRawOriginal('description_html'))->toBe('<h2>Updated</h2>')
            ->and($page->refresh()->getRawOriginal('body_html'))->toBe('<blockquote>Quoted</blockquote>');
    });
});

describe('theme preview isolation', function () {
    it('only exposes a draft preview to an authorized admin of that storefront tenant', function () {
        $storeA = createStoreContext();
        $published = Theme::factory()->for($storeA['store'])->create([
            'name' => 'Published A',
            'status' => 'published',
        ]);
        $published->settings()->create(['settings_json' => [
            'colors' => ['primary' => '#112233'],
        ]]);
        $draft = Theme::factory()->draft()->for($storeA['store'])->create(['name' => 'Draft A']);
        $draft->settings()->create(['settings_json' => [
            'colors' => ['primary' => '#abcdef'],
        ]]);

        $storeB = createStoreContext();
        $foreignDraft = Theme::factory()->draft()->for($storeB['store'])->create(['name' => 'Draft B']);
        $foreignDraft->settings()->create(['settings_json' => [
            'colors' => ['primary' => '#fedcba'],
        ]]);

        $previewUrl = "http://{$storeA['domain']->hostname}/?theme_preview={$draft->id}";

        $this->withSession(["theme_preview.{$draft->id}" => ['colors' => ['primary' => '#ff0000']]])
            ->get($previewUrl)
            ->assertOk()
            ->assertSee('--storefront-primary: #112233', false)
            ->assertDontSee('#abcdef', false)
            ->assertDontSee('#ff0000', false);

        $this->actingAs($storeB['user'], 'web')
            ->get($previewUrl)
            ->assertOk()
            ->assertSee('--storefront-primary: #112233', false)
            ->assertDontSee('#abcdef', false);

        $this->actingAs($storeA['user'], 'web')
            ->get($previewUrl)
            ->assertOk()
            ->assertSee('--storefront-primary: #abcdef', false)
            ->assertDontSee('--storefront-primary: #112233', false);

        $this->get("http://{$storeA['domain']->hostname}/?theme_preview={$foreignDraft->id}")
            ->assertOk()
            ->assertSee('--storefront-primary: #112233', false)
            ->assertDontSee('#fedcba', false);
    });
});
