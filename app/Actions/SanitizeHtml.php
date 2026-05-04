<?php

namespace App\Actions;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

class SanitizeHtml
{
    /**
     * @var array<string, list<string>>
     */
    private const array AllowedElements = [
        'p' => [],
        'br' => [],
        'strong' => [],
        'em' => [],
        'u' => [],
        'ol' => [],
        'ul' => [],
        'li' => [],
        'a' => ['href'],
        'img' => ['src', 'alt'],
        'h1' => [],
        'h2' => [],
        'h3' => [],
        'h4' => [],
        'h5' => [],
        'h6' => [],
        'blockquote' => [],
        'table' => [],
        'thead' => [],
        'tbody' => [],
        'tr' => [],
        'th' => [],
        'td' => [],
        'div' => [],
        'span' => [],
    ];

    /**
     * @var list<string>
     */
    private const array DangerousElements = [
        'base',
        'button',
        'canvas',
        'embed',
        'form',
        'iframe',
        'input',
        'link',
        'math',
        'meta',
        'object',
        'script',
        'select',
        'style',
        'svg',
        'textarea',
    ];

    /**
     * @var list<string>
     */
    private const array EmptyAllowedElements = ['br', 'img'];

    public function __invoke(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $html = trim($html);

        if ($html === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        try {
            $document->loadHTML($this->wrapHtml($html), LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
            $root = $this->rootElement($document);

            if (! $root instanceof DOMElement) {
                return '';
            }

            $this->sanitizeChildren($root);
            $this->removeEmptyElements($root);

            return trim($this->innerHtml($root));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function wrapHtml(string $html): string
    {
        return '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body><div id="__sanitize_html_root">'.$html.'</div></body></html>';
    }

    private function rootElement(DOMDocument $document): ?DOMElement
    {
        $root = (new DOMXPath($document))->query('//*[@id="__sanitize_html_root"]')?->item(0);

        return $root instanceof DOMElement ? $root : null;
    }

    private function sanitizeChildren(DOMNode $parent): void
    {
        foreach ($this->childNodes($parent) as $child) {
            if ($child instanceof DOMElement) {
                $this->sanitizeElement($child);

                continue;
            }

            if ($child->nodeType !== XML_TEXT_NODE) {
                $parent->removeChild($child);
            }
        }
    }

    private function sanitizeElement(DOMElement $element): void
    {
        $tagName = strtolower($element->tagName);

        if (in_array($tagName, self::DangerousElements, true)) {
            $element->parentNode?->removeChild($element);

            return;
        }

        if (! array_key_exists($tagName, self::AllowedElements)) {
            $this->sanitizeChildren($element);
            $this->unwrapElement($element);

            return;
        }

        $this->sanitizeAttributes($element, self::AllowedElements[$tagName]);
        $this->sanitizeChildren($element);
    }

    /**
     * @param  list<string>  $allowedAttributes
     */
    private function sanitizeAttributes(DOMElement $element, array $allowedAttributes): void
    {
        foreach ($this->attributeNames($element) as $attributeName) {
            $normalizedName = strtolower($attributeName);

            if (! in_array($normalizedName, $allowedAttributes, true)) {
                $element->removeAttribute($attributeName);

                continue;
            }

            $value = trim($element->getAttribute($attributeName));

            if ($this->isUrlAttribute($normalizedName) && ! $this->isSafeUrl($normalizedName, $value)) {
                $element->removeAttribute($attributeName);

                continue;
            }

            $element->setAttribute($normalizedName, $value);
        }
    }

    private function isUrlAttribute(string $attributeName): bool
    {
        return in_array($attributeName, ['href', 'src'], true);
    }

    private function isSafeUrl(string $attributeName, string $value): bool
    {
        if ($value === '' || preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            return false;
        }

        $scheme = parse_url($value, PHP_URL_SCHEME);

        if ($scheme === null) {
            return true;
        }

        $allowedSchemes = $attributeName === 'href'
            ? ['http', 'https', 'mailto', 'tel']
            : ['http', 'https'];

        return in_array(strtolower($scheme), $allowedSchemes, true);
    }

    private function unwrapElement(DOMElement $element): void
    {
        $parent = $element->parentNode;

        if (! $parent instanceof DOMNode) {
            return;
        }

        while ($element->firstChild instanceof DOMNode) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }

    private function removeEmptyElements(DOMNode $parent): void
    {
        foreach ($this->childNodes($parent) as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            $this->removeEmptyElements($child);

            if ($this->isEmptyElement($child)) {
                $child->parentNode?->removeChild($child);
            }
        }
    }

    private function isEmptyElement(DOMElement $element): bool
    {
        $tagName = strtolower($element->tagName);

        if ($tagName === 'br') {
            return false;
        }

        if ($tagName === 'img') {
            return ! $element->hasAttribute('src');
        }

        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                return false;
            }

            if ($child->nodeType === XML_TEXT_NODE && trim((string) $child->textContent) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<DOMNode>
     */
    private function childNodes(DOMNode $node): array
    {
        return iterator_to_array($node->childNodes);
    }

    /**
     * @return list<string>
     */
    private function attributeNames(DOMElement $element): array
    {
        return collect($element->attributes)
            ->map(fn ($attribute): string => $attribute->nodeName)
            ->all();
    }

    private function innerHtml(DOMElement $element): string
    {
        return collect($element->childNodes)
            ->map(fn (DOMNode $child): string => $element->ownerDocument?->saveHTML($child) ?: '')
            ->implode('');
    }
}
