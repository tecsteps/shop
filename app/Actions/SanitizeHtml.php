<?php

namespace App\Actions;

class SanitizeHtml
{
    /**
     * @var array<string, list<string>>
     */
    private const ALLOWED_ELEMENTS = [
        'a' => ['href'],
        'blockquote' => [],
        'br' => [],
        'div' => [],
        'em' => [],
        'h1' => [],
        'h2' => [],
        'h3' => [],
        'h4' => [],
        'h5' => [],
        'h6' => [],
        'img' => ['src', 'alt'],
        'li' => [],
        'ol' => [],
        'p' => [],
        'span' => [],
        'strong' => [],
        'table' => [],
        'tbody' => [],
        'td' => [],
        'th' => [],
        'thead' => [],
        'tr' => [],
        'u' => [],
        'ul' => [],
    ];

    /**
     * @var list<string>
     */
    private const DROP_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed'];

    public function __invoke(?string $html): ?string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return null;
        }

        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument('1.0', 'UTF-8');
        $document->loadHTML(
            '<!DOCTYPE html><html><body><div id="sanitize-root">'.$html.'</div></body></html>',
            LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('sanitize-root');

        if (! $root instanceof \DOMElement) {
            return null;
        }

        $this->sanitizeChildren($root);

        $clean = '';

        foreach ($root->childNodes as $child) {
            $clean .= $document->saveHTML($child);
        }

        $clean = trim($clean);

        return $clean === '' ? null : $clean;
    }

    private function sanitizeChildren(\DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node->nodeType === XML_COMMENT_NODE) {
                $parent->removeChild($node);

                continue;
            }

            if (! $node instanceof \DOMElement) {
                continue;
            }

            $tagName = strtolower($node->tagName);

            if (in_array($tagName, self::DROP_WITH_CONTENT, true)) {
                $parent->removeChild($node);

                continue;
            }

            $this->sanitizeChildren($node);

            if (! array_key_exists($tagName, self::ALLOWED_ELEMENTS)) {
                $this->unwrap($node);

                continue;
            }

            $this->sanitizeAttributes($node, self::ALLOWED_ELEMENTS[$tagName]);

            if (! in_array($tagName, ['br', 'img'], true) && trim($node->textContent) === '' && $node->childElementCount === 0) {
                $node->parentNode?->removeChild($node);
            }
        }
    }

    /**
     * @param  list<string>  $allowedAttributes
     */
    private function sanitizeAttributes(\DOMElement $node, array $allowedAttributes): void
    {
        foreach (iterator_to_array($node->attributes) as $attribute) {
            $name = strtolower($attribute->name);
            $value = trim($attribute->value);

            if (! in_array($name, $allowedAttributes, true) || ! $this->isSafeAttribute($name, $value)) {
                $node->removeAttribute($attribute->name);
            }
        }
    }

    private function isSafeAttribute(string $name, string $value): bool
    {
        if (! in_array($name, ['href', 'src'], true)) {
            return true;
        }

        if (str_starts_with($value, '/') || str_starts_with($value, '#')) {
            return true;
        }

        $scheme = parse_url($value, PHP_URL_SCHEME);

        return in_array($scheme, $name === 'href' ? ['http', 'https', 'mailto'] : ['http', 'https'], true);
    }

    private function unwrap(\DOMElement $node): void
    {
        $parent = $node->parentNode;

        if (! $parent instanceof \DOMNode) {
            return;
        }

        while ($node->firstChild instanceof \DOMNode) {
            $parent->insertBefore($node->firstChild, $node);
        }

        $parent->removeChild($node);
    }
}
