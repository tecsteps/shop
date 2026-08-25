<?php

namespace App\Actions;

class SanitizeHtml
{
    /**
     * @var list<string>
     */
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'em', 'u', 'ol', 'ul', 'li', 'a', 'img',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote',
        'table', 'thead', 'tbody', 'tr', 'th', 'td', 'div', 'span',
    ];

    /**
     * @var array<string, list<string>>
     */
    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href'],
        'img' => ['src', 'alt'],
    ];

    public function sanitize(string $html): string
    {
        $allowedTags = implode('', array_map(fn (string $tag) => '<'.$tag.'>', self::ALLOWED_TAGS));

        $sanitized = strip_tags($html, $allowedTags);

        foreach (self::ALLOWED_TAGS as $tag) {
            $allowedAttributes = self::ALLOWED_ATTRIBUTES[$tag] ?? [];

            $sanitized = preg_replace_callback('/<'.$tag.'\s+([^>]*)>/i', function (array $matches) use ($tag, $allowedAttributes) {
                $attributes = $this->filterAttributes($matches[1], $allowedAttributes);

                return '<'.$tag.($attributes !== '' ? ' '.$attributes : '').'>';
            }, $sanitized) ?? $sanitized;
        }

        return $sanitized;
    }

    /**
     * @param  list<string>  $allowedAttributes
     */
    private function filterAttributes(string $attributeString, array $allowedAttributes): string
    {
        if ($allowedAttributes === []) {
            return '';
        }

        preg_match_all('/([a-z-]+)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', $attributeString, $matches, PREG_SET_ORDER);

        $kept = [];

        foreach ($matches as $match) {
            $name = strtolower($match[1]);

            if (in_array($name, $allowedAttributes, true)) {
                $kept[] = $name.'='.$match[2];
            }
        }

        return implode(' ', $kept);
    }
}
