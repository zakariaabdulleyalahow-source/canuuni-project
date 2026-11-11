<?php
// includes/slug.php

if (!function_exists('slugify')) {
    /**
     * Converts a string like "Wireless Mouse (Black) 2.4GHz" into
     * "wireless-mouse-black-2-4ghz". Safe for use in a URL path segment.
     */
    function slugify(string $text): string {
        // Transliterate accented characters where possible.
        $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        if ($transliterated !== false) {
            $text = $transliterated;
        }
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');
        return $text !== '' ? $text : 'item';
    }
}

if (!function_exists('product_url')) {
    /**
     * Builds the canonical SEO-friendly URL for a product, relative to the site root.
     * e.g. product_url(10, 'Wireless Mouse') => '/product/10/wireless-mouse'
     */
    function product_url($id, string $name): string {
        // Notice the leading slash / at the beginning:
        return '/product/' . (int) $id . '/' . slugify($name);
    }
}