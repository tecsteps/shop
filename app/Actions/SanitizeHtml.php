<?php

namespace App\Actions;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Sanitizes rich-text HTML (product descriptions, page bodies) against an
 * allowlist of elements and attributes per spec 06 §4.5. Everything else is
 * stripped: disallowed elements are unwrapped (script/style removed with
 * their content), disallowed attributes removed, dangerous URL schemes
 * neutralized, and empty elements pruned.
 */
class SanitizeHtml
{
    /**
     * Allowed elements mapped to their allowed attributes.
     *
     * @var array<string, list<string>>
     */
    private const ALLOWED = [
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
     * Elements removed together with their text content.
     *
     * @var list<string>
     */
    private const REMOVE_WITH_CONTENT = ['script', 'style'];

    /**
     * Allowed URL schemes for href/src attributes (relative URLs pass too).
     *
     * @var list<string>
     */
    private const ALLOWED_SCHEMES = ['http', 'https', 'mailto'];

    /**
     * Elements pruned when they contain no text and no element children.
     *
     * @var list<string>
     */
    private const PRUNE_WHEN_EMPTY = [
        'p', 'strong', 'em', 'u', 'ol', 'ul', 'li', 'a',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote',
        'table', 'thead', 'tbody', 'tr', 'div', 'span',
    ];

    /**
     * Sanitize the given HTML fragment. Null and empty input pass through.
     */
    public function __invoke(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return $html;
        }

        $document = new DOMDocument;
        $document->loadHTML(
            '<?xml encoding="UTF-8"?>'.$html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING
        );

        // Remove the encoding workaround processing instruction.
        foreach (iterator_to_array($document->childNodes) as $child) {
            if ($child instanceof \DOMProcessingInstruction) {
                $document->removeChild($child);
            }
        }

        $this->sanitizeChildren($document);
        $this->pruneEmptyElements($document);

        $output = '';
        foreach ($document->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return trim($output);
    }

    /**
     * Recursively sanitize all child nodes of the given parent.
     */
    private function sanitizeChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::REMOVE_WITH_CONTENT, true)) {
                $parent->removeChild($child);

                continue;
            }

            if (! array_key_exists($tag, self::ALLOWED)) {
                // Unwrap: keep the children, drop the element itself.
                $this->sanitizeChildren($child);

                while ($child->firstChild !== null) {
                    $parent->insertBefore($child->firstChild, $child);
                }
                $parent->removeChild($child);

                continue;
            }

            $this->sanitizeAttributes($child, $tag);
            $this->sanitizeChildren($child);
        }
    }

    /**
     * Strip attributes outside the allowlist and neutralize unsafe URLs.
     *
     * @param  key-of<self::ALLOWED>  $tag
     */
    private function sanitizeAttributes(DOMElement $element, string $tag): void
    {
        $allowedAttributes = self::ALLOWED[$tag];

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->nodeName);

            if (! in_array($name, $allowedAttributes, true)) {
                $element->removeAttribute($attribute->nodeName);

                continue;
            }

            if (in_array($name, ['href', 'src'], true) && ! $this->isSafeUrl($attribute->nodeValue)) {
                $element->removeAttribute($attribute->nodeName);
            }
        }
    }

    /**
     * Allow relative URLs and safe schemes only (blocks javascript:, data:, ...).
     */
    private function isSafeUrl(string $url): bool
    {
        $url = trim($url);

        if ($url === '' || str_starts_with($url, '#')) {
            return true;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        if ($scheme === null) {
            return true; // relative URL
        }

        return in_array(strtolower($scheme), self::ALLOWED_SCHEMES, true);
    }

    /**
     * Whether the element has at least one child element.
     */
    private function hasElementChild(DOMElement $element): bool
    {
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove elements that carry no content (repeatedly, for nesting).
     */
    private function pruneEmptyElements(DOMDocument $document): void
    {
        $xpath = new DOMXPath($document);

        do {
            $removed = 0;

            foreach (self::PRUNE_WHEN_EMPTY as $tag) {
                foreach (iterator_to_array($xpath->query('//'.$tag) ?: []) as $element) {
                    /** @var DOMElement $element */
                    if (trim($element->textContent) === '' && ! $this->hasElementChild($element)) {
                        $element->parentNode?->removeChild($element);
                        $removed++;
                    }
                }
            }
        } while ($removed > 0);
    }
}
