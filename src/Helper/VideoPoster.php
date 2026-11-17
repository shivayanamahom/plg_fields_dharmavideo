<?php

declare(strict_types=1);

namespace Dharma\Plugin\Fields\Dharmavideo\Helper;

defined('_JEXEC') or die;

/** Fetch the provider thumbnail at edit time and render it from the local filesystem. */
final class VideoPoster
{
    public const DIRECTORY = 'images/dharmavideo/posters';

    public static function images(object $item): array
    {
        $images = json_decode((string) ($item->images ?? ''), true);

        return is_array($images) ? $images : [];
    }

    public static function firstVideo(mixed $rows): ?array
    {
        if (is_string($rows)) {
            $rows = json_decode($rows, true);
        }

        foreach ((array) $rows as $row) {
            if ($video = VideoProvider::parse(((array) $row)['url'] ?? null)) {
                return $video;
            }
        }

        return null;
    }

    public static function cached(array $video): string
    {
        $path = self::path($video);

        return is_file(JPATH_ROOT . '/' . $path) ? $path : '';
    }

    public static function cache(array $video, float $deadline): void
    {
        if (self::cached($video) !== '' || !function_exists('imagecreatefromstring')) {
            return;
        }

        if ($video['provider'] === 'rutube') {
            $api = 'https://rutube.ru/api/play/options/' . $video['id'] . '/';
            $parts = parse_url($video['embed']);
            if (!empty($parts['query'])) {
                $api .= '?' . $parts['query'];
            }
        } else {
            $api = 'https://api.vk.com/method/video.getOembed?' . http_build_query([
                'url' => $video['url'],
                'maxwidth' => 1280,
                'maxheight' => 720,
                'v' => '5.199',
            ]);
        }

        $metadata = json_decode(self::download($api, 1024 * 1024, $deadline), true);
        $url = $video['provider'] === 'rutube'
            ? ($metadata['thumbnail_url'] ?? '')
            : ($metadata['response']['thumbnail_url'] ?? '');

        if (!is_string($url) || !self::isPosterUrl($url, $video['provider'])) {
            return;
        }

        $bytes = self::download($url, 5 * 1024 * 1024, $deadline);
        $size = $bytes === '' ? false : @getimagesizefromstring($bytes);
        if (!$size || !in_array($size[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)
            || $size[0] < 1 || $size[1] < 1 || $size[0] > 4500 || $size[1] > 4500 || $size[0] * $size[1] > 16000000) {
            return;
        }

        $image = @imagecreatefromstring($bytes);
        if (!$image) {
            return;
        }

        $scale = min(1, 1280 / $size[0], 1280 / $size[1]);
        $thumbnail = imagecreatetruecolor(max(1, (int) round($size[0] * $scale)), max(1, (int) round($size[1] * $scale)));
        if (!$thumbnail) {
            return;
        }

        imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, imagesx($thumbnail), imagesy($thumbnail), $size[0], $size[1]);
        $dir = JPATH_ROOT . '/' . self::DIRECTORY;
        if ((!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) || is_link($dir)) {
            return;
        }

        $temporary = @tempnam($dir, 'poster-');
        if ($temporary === false) {
            return;
        }

        try {
            if (imagejpeg($thumbnail, $temporary, 87)) {
                @chmod($temporary, 0644);
                @rename($temporary, JPATH_ROOT . '/' . self::path($video));
            }
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }

    private static function path(array $video): string
    {
        return self::DIRECTORY . '/' . hash('sha256', $video['embed']) . '.jpg';
    }

    private static function isPosterUrl(string $url, string $provider): bool
    {
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        if (($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
            return false;
        }

        return $provider === 'rutube'
            ? in_array($host, ['pic.rutube.ru', 'pic.rtbcdn.ru'], true)
            : preg_match('/^[a-z0-9-]+\.(?:userapi\.com|okcdn\.ru)$/D', $host) === 1;
    }

    private static function download(string $url, int $limit, float $deadline): string
    {
        $remaining = $deadline - microtime(true);
        if ($remaining <= 0) {
            return '';
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => min(3.0, $remaining),
                'follow_location' => 0,
                'user_agent' => 'Dharma Video for Joomla',
                'header' => "Connection: close\r\n",
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);

        $data = @file_get_contents($url, false, $context, 0, $limit + 1);

        $headers = function_exists('http_get_last_response_headers') ? http_get_last_response_headers() : ($http_response_header ?? []);

        return is_string($data) && strlen($data) <= $limit
            && preg_match('~^HTTP/\d(?:\.\d)? 200\b~', $headers[0] ?? '') === 1 ? $data : '';
    }
}
