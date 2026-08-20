<?php

namespace App\Support;

class HtmlSanitizer
{
    public function sanitize(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $sanitized = strip_tags($html, '<p><br><strong><em><u><ul><ol><li><a><h1><h2><h3><blockquote>');
        $sanitized = preg_replace('/\s+on[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $sanitized) ?? $sanitized;

        return preg_replace('/(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2/i', '$1=$2$2', $sanitized) ?? $sanitized;
    }
}
