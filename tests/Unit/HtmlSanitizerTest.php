<?php

use App\Support\HtmlSanitizer;

it('keeps safe formatting while removing executable elements and event handlers', function () {
    $html = app(HtmlSanitizer::class)->sanitize('<p onclick="steal()">Hello <strong>there</strong><script>alert(1)</script></p>');

    expect($html)->toContain('<p>Hello <strong>there</strong></p>')
        ->and($html)->not->toContain('onclick')
        ->and($html)->not->toContain('alert(1)');
});

it('removes unsafe links and protects links opened in a new tab', function () {
    $html = app(HtmlSanitizer::class)->sanitize('<a href="javascript:alert(1)">unsafe</a><a href="https://shop.test" target="_blank">safe</a>');

    expect($html)->toContain('<a>unsafe</a>')
        ->and($html)->toContain('rel="noopener noreferrer"')
        ->and($html)->not->toContain('javascript:');
});
