<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

class HtmlSanitizer
{
    private const ALLOWED_TAGS = ['p', 'br', 'h1', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'strong', 'em', 'b', 'i', 'a', 'blockquote', 'img'];

    public function sanitize(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?><div id="sanitizer-root">'.(string) $html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('sanitizer-root');

        if (! $root) {
            return e(strip_tags((string) $html));
        }

        $this->sanitizeChildren($root);
        $safe = '';

        foreach ($root->childNodes as $child) {
            $safe .= $document->saveHTML($child);
        }

        return $safe;
    }

    private function sanitizeChildren(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMText) {
                continue;
            }

            if (! $child instanceof DOMElement || ! in_array(strtolower($child->tagName), self::ALLOWED_TAGS, true)) {
                if ($child instanceof DOMElement && in_array(strtolower($child->tagName), ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math'], true)) {
                    $node->removeChild($child);

                    continue;
                }

                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }

                $node->removeChild($child);

                continue;
            }

            $this->sanitizeAttributes($child);

            if (strtolower($child->tagName) !== 'img') {
                $this->sanitizeChildren($child);
            }
        }
    }

    private function sanitizeAttributes(DOMElement $element): void
    {
        $tag = strtolower($element->tagName);

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->name);
            $allowed = $tag === 'a'
                ? in_array($name, ['href', 'title', 'target', 'rel'], true)
                : ($tag === 'img' && in_array($name, ['src', 'alt', 'width', 'height'], true));

            if (! $allowed) {
                $element->removeAttribute($attribute->name);
            }
        }

        $urlAttribute = $tag === 'a' ? 'href' : ($tag === 'img' ? 'src' : null);

        if ($urlAttribute !== null && $element->hasAttribute($urlAttribute) && ! $this->isSafeUrl($element->getAttribute($urlAttribute))) {
            $element->removeAttribute($urlAttribute);
        }

        if ($tag === 'a' && $element->getAttribute('target') === '_blank') {
            $element->setAttribute('rel', 'noopener noreferrer');
        }

        if ($tag === 'img') {
            $element->setAttribute('loading', 'lazy');
        }
    }

    private function isSafeUrl(string $url): bool
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($url === '' || str_starts_with($url, '//')) {
            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return $scheme === null || in_array(strtolower($scheme), ['http', 'https', 'mailto', 'tel'], true);
    }
}
