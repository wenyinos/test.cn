<?php
/**
 * @copyright 2026 wenyinos <ruojiner@hotmail.com>
 * @license MIT License
 */
if (!defined('ROOT_PATH')) {
    http_response_code(403);
    exit('Forbidden');
}

if (!function_exists('template_value')) {
    function template_value($value, string $default = ''): string {
        $value = is_string($value) ? trim($value) : '';
        return $value !== '' ? $value : $default;
    }

    function template_href($url, string $fallback = ''): string {
        $url = is_string($url) ? trim($url) : '';
        return is_valid_asset_url($url) ? $url : $fallback;
    }

    function template_js($value): string {
        return json_encode((string)$value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    function template_nav_links($raw, array $fallback = [], int $max = 10): array {
        $links = normalize_nav_links((string)$raw, $max);
        if (!empty($links)) {
            return $links;
        }

        $normalized = [];
        foreach ($fallback as $item) {
            if (!is_array($item)) {
                continue;
            }

            $url = template_href($item['url'] ?? '', '');
            if ($url === '') {
                continue;
            }

            $link = [
                'name' => template_value($item['name'] ?? '', '链接 ' . (count($normalized) + 1)),
                'url' => $url,
            ];

            $icon = normalize_nav_icon((string)($item['icon'] ?? ''));
            if ($icon !== '') {
                $link['icon'] = $icon;
            }

            $normalized[] = $link;
            if (count($normalized) >= $max) {
                break;
            }
        }

        return $normalized;
    }

    function template_nav_payload($raw, array $fallback = [], int $max = 10): array {
        $payload = decode_nav_payload((string)$raw);
        return [
            'links' => template_nav_links($raw, $fallback, $max),
            'meta' => is_array($payload['meta'] ?? null) ? $payload['meta'] : [],
        ];
    }

    function template_icon_data($icon, string $fallback = '🔗'): array {
        $icon = is_string($icon) ? trim($icon) : '';
        if ($icon !== '') {
            $asset = template_href($icon, '');
            if ($asset !== '' && preg_match('/\.(png|jpe?g|gif|webp|svg)(\?.*)?$/i', $asset)) {
                return ['type' => 'image', 'value' => $asset];
            }

            $icon = strip_tags($icon);
            if (function_exists('mb_substr')) {
                $icon = mb_substr($icon, 0, 6, 'UTF-8');
            } else {
                $icon = substr($icon, 0, 6);
            }
            if ($icon !== '') {
                return ['type' => 'text', 'value' => $icon];
            }
        }

        return ['type' => 'text', 'value' => $fallback];
    }
}
