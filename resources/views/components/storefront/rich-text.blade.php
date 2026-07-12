@props([
    'html' => '',
])

@php
    $source = (string) $html;
    $source = preg_replace(
        '#<(script|style|iframe|object|embed|template|svg|math)\b[^>]*>.*?</\1\s*>#is',
        '',
        $source,
    ) ?? '';
    $source = preg_replace('#<(script|style|iframe|object|embed|template|svg|math)\b[^>]*/?>#is', '', $source) ?? '';

    $allowedTags = '<p><br><strong><em><b><i><u><s><h1><h2><h3><h4><h5><h6><ul><ol><li><a><blockquote><code><pre><hr>';
    $source = strip_tags($source, $allowedTags);
    $sanitized = e(strip_tags($source));

    if (class_exists(\DOMDocument::class) && $source !== '') {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $previousSetting = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="utf-8" ?><div id="storefront-rich-text-root">'.$source.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousSetting);

        $xpath = new \DOMXPath($document);

        foreach ($xpath->query('//*[@*]') ?: [] as $element) {
            $tagName = strtolower($element->nodeName);

            foreach (iterator_to_array($element->attributes) as $attribute) {
                $attributeName = strtolower($attribute->nodeName);
                $isAllowedAnchorAttribute = $tagName === 'a'
                    && in_array($attributeName, ['href', 'title', 'target', 'rel'], true);
                $isAllowedId = $attributeName === 'id'
                    && preg_match('/^[a-zA-Z][a-zA-Z0-9_:\-.]*$/', $attribute->nodeValue) === 1;

                if (! $isAllowedAnchorAttribute && ! $isAllowedId) {
                    $element->removeAttributeNode($attribute);
                }
            }

            if ($tagName !== 'a') {
                continue;
            }

            $href = trim(html_entity_decode($element->getAttribute('href'), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $scheme = strtolower((string) parse_url($href, PHP_URL_SCHEME));

            if ($href === '' || preg_match('/[\x00-\x1F\x7F]/', $href) === 1 || ($scheme !== '' && ! in_array($scheme, ['http', 'https', 'mailto', 'tel'], true))) {
                $element->removeAttribute('href');
            }

            if ($element->hasAttribute('target') && $element->getAttribute('target') !== '_blank') {
                $element->removeAttribute('target');
            }

            if ($element->getAttribute('target') === '_blank') {
                $element->setAttribute('rel', 'noopener noreferrer');
            } else {
                $element->removeAttribute('rel');
            }
        }

        foreach ($xpath->query('//comment()') ?: [] as $comment) {
            $comment->parentNode?->removeChild($comment);
        }

        $root = $document->getElementById('storefront-rich-text-root');

        if ($root) {
            $sanitized = '';

            foreach ($root->childNodes as $child) {
                $sanitized .= $document->saveHTML($child);
            }
        }
    }
@endphp

@if (trim(strip_tags($sanitized)) !== '')
    <div {{ $attributes->class('storefront-prose') }}>
        {!! $sanitized !!}
    </div>
@endif
