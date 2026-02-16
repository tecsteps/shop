<?php

if (! function_exists('sanitize_html')) {
    /**
     * Sanitize HTML by stripping all tags except a safe allowlist.
     */
    function sanitize_html(?string $html): ?string
    {
        if (! $html) {
            return null;
        }

        return strip_tags($html, '<p><br><strong><em><ul><ol><li><h2><h3><h4><a><img>');
    }
}
