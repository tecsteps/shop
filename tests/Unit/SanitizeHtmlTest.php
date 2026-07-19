<?php

use App\Actions\SanitizeHtml;

test('allowed tags survive sanitization', function () {
    $html = '<p>Hello <strong>bold</strong> and <em>italic</em></p><ul><li>one</li><li>two</li></ul><h2>Title</h2><blockquote>quote</blockquote>';

    $result = (new SanitizeHtml)($html);

    expect($result)
        ->toContain('<p>')
        ->toContain('<strong>bold</strong>')
        ->toContain('<em>italic</em>')
        ->toContain('<ul><li>one</li><li>two</li></ul>')
        ->toContain('<h2>Title</h2>')
        ->toContain('<blockquote>quote</blockquote>');
});

test('script and style elements are removed with their content', function () {
    $html = '<p>Safe</p><script>alert("xss")</script><style>body{display:none}</style>';

    $result = (new SanitizeHtml)($html);

    expect($result)
        ->not->toContain('script')
        ->not->toContain('alert')
        ->not->toContain('display:none')
        ->toContain('<p>Safe</p>');
});

test('disallowed elements are unwrapped but their text is kept', function () {
    $html = '<div><iframe src="https://evil.test"></iframe><section><p>Kept</p></section></div>';

    $result = (new SanitizeHtml)($html);

    expect($result)
        ->not->toContain('iframe')
        ->not->toContain('section')
        ->toContain('<p>Kept</p>');
});

test('event handlers and other attributes are stripped', function () {
    $html = '<p onclick="steal()" class="fancy">Text</p><a href="https://example.com" target="_blank" onmouseover="x()">link</a>';

    $result = (new SanitizeHtml)($html);

    expect($result)
        ->not->toContain('onclick')
        ->not->toContain('class')
        ->not->toContain('onmouseover')
        ->not->toContain('target')
        ->toContain('<a href="https://example.com">link</a>');
});

test('javascript: hrefs are neutralized', function () {
    $html = '<a href="javascript:alert(1)">click</a>';

    $result = (new SanitizeHtml)($html);

    expect($result)
        ->not->toContain('javascript:')
        ->toContain('<a>click</a>');
});

test('img src and alt attributes are kept', function () {
    $html = '<img src="https://example.com/pic.jpg" alt="A picture" width="100" onerror="hack()">';

    $result = (new SanitizeHtml)($html);

    expect($result)
        ->toContain('src="https://example.com/pic.jpg"')
        ->toContain('alt="A picture"')
        ->not->toContain('width')
        ->not->toContain('onerror');
});

test('empty elements are removed', function () {
    $html = '<p></p><div><span></span></div><p>Real content</p>';

    $result = (new SanitizeHtml)($html);

    expect($result)
        ->not->toContain('<span>')
        ->toContain('<p>Real content</p>')
        ->not->toContain('<p></p>');
});

test('tables are preserved', function () {
    $html = '<table><thead><tr><th>Head</th></tr></thead><tbody><tr><td>Cell</td></tr></tbody></table>';

    $result = (new SanitizeHtml)($html);

    expect($result)
        ->toContain('<table>')
        ->toContain('<th>Head</th>')
        ->toContain('<td>Cell</td>');
});

test('null and empty input pass through', function () {
    expect((new SanitizeHtml)(null))->toBeNull()
        ->and((new SanitizeHtml)(''))->toBe('')
        ->and((new SanitizeHtml)('   '))->toBe('   ');
});
