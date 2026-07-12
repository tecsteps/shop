<?php

namespace App\Actions;

use DOMDocument;
use DOMElement;
use DOMXPath;

final class SanitizeHtml
{
    /** @var array<string, list<string>> */
    private const ALLOWED = [
        'p' => [], 'br' => [], 'strong' => [], 'em' => [], 'u' => [],
        'ol' => [], 'ul' => [], 'li' => [], 'a' => ['href'], 'img' => ['src', 'alt'],
        'h1' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [], 'h6' => [],
        'blockquote' => [], 'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [],
        'th' => [], 'td' => [], 'div' => [], 'span' => [],
    ];

    public function execute(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $html = preg_replace('#<(script|style|iframe|object|embed|template|svg|math)\b[^>]*>.*?</\1\s*>#is', '', $html) ?? '';
        $html = preg_replace('#<(script|style|iframe|object|embed|template|svg|math)\b[^>]*/?>#is', '', $html) ?? '';
        $allowedTags = '<'.implode('><', array_keys(self::ALLOWED)).'>';
        $html = strip_tags($html, $allowedTags);
        if ($html === '' || ! class_exists(DOMDocument::class)) {
            return trim($html);
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="utf-8" ?><div id="sanitize-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($document);

        foreach ($xpath->query('//*[@*]') ?: [] as $element) {
            if (! $element instanceof DOMElement || $element->getAttribute('id') === 'sanitize-root') {
                continue;
            }
            $tag = mb_strtolower($element->tagName);
            foreach (iterator_to_array($element->attributes) as $attribute) {
                if (! in_array(mb_strtolower($attribute->nodeName), self::ALLOWED[$tag] ?? [], true)) {
                    $element->removeAttributeNode($attribute);
                }
            }
            foreach (['href', 'src'] as $attribute) {
                if ($element->hasAttribute($attribute) && ! $this->safeUrl($element->getAttribute($attribute), $attribute === 'src')) {
                    $element->removeAttribute($attribute);
                }
            }
        }

        foreach ($xpath->query('//comment()') ?: [] as $comment) {
            $comment->parentNode?->removeChild($comment);
        }

        $elements = iterator_to_array($xpath->query('//*[not(@id="sanitize-root")]') ?: []);
        foreach (array_reverse($elements) as $element) {
            if ($element instanceof DOMElement
                && ! in_array(mb_strtolower($element->tagName), ['br', 'img'], true)
                && trim($element->textContent) === ''
                && $element->getElementsByTagName('img')->length === 0
                && $element->getElementsByTagName('br')->length === 0) {
                $element->parentNode?->removeChild($element);
            }
        }

        $root = $document->getElementById('sanitize-root');
        if ($root === null) {
            return '';
        }

        $result = '';
        foreach ($root->childNodes as $child) {
            $result .= $document->saveHTML($child);
        }

        return trim($result);
    }

    private function safeUrl(string $url, bool $image): bool
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url) === 1) {
            return false;
        }
        $scheme = mb_strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $schemes = $image ? ['http', 'https'] : ['http', 'https', 'mailto', 'tel'];

        return $scheme === '' || in_array($scheme, $schemes, true);
    }
}
