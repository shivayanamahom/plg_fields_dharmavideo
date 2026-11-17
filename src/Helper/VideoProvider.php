<?php

declare(strict_types=1);

namespace Dharma\Plugin\Fields\Dharmavideo\Helper;

defined('_JEXEC') or die;

/** Derive embed URLs locally; never fetch user-supplied URLs. */
final class VideoProvider
{
    public const MAX_URL = 2048;

    /** @return array{provider: string, id: string, embed: string, url: string}|null */
    public static function parse(mixed $value): ?array
    {
        if (!is_string($value)) {
            return null;
        }

        $url = trim($value);

        if ($url === '' || strlen($url) > self::MAX_URL || preg_match('/[\x00-\x20\x7f]/', $url)) {
            return null;
        }

        if (!str_contains($url, '://')) {
            $url = 'https://' . $url;
        }

        $parts = parse_url($url);

        if (!$parts || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
            return null;
        }

        $host = strtolower($parts['host'] ?? '');
        $path = rtrim($parts['path'] ?? '', '/');
        parse_str($parts['query'] ?? '', $query);

        if (in_array($host, ['rutube.ru', 'www.rutube.ru'], true)) {
            if (!preg_match('~^/(?:video(?:/private)?|shorts|play/embed)/([a-fA-F0-9]{32})$~D', $path, $match)) {
                return null;
            }

            $token = $query['p'] ?? '';
            $private = str_starts_with($path, '/video/private/');
            if (!self::validToken($token) || ($private && $token === '')) {
                return null;
            }

            $id = strtolower($match[1]);
            $suffix = $token !== '' ? '?p=' . $token : '';

            return [
                'provider' => 'rutube',
                'id' => $id,
                'embed' => 'https://rutube.ru/play/embed/' . $id . '/' . $suffix,
                'url' => 'https://rutube.ru/video/' . ($token !== '' ? 'private/' : '') . $id . '/' . $suffix,
            ];
        }

        if (!in_array($host, ['vk.com', 'www.vk.com', 'm.vk.com', 'vkvideo.ru', 'www.vkvideo.ru', 'm.vkvideo.ru'], true)) {
            return null;
        }

        $id = '';
        if ($path === '/video_ext.php' && is_string($query['oid'] ?? null) && is_string($query['id'] ?? null)) {
            $id = $query['oid'] . '_' . $query['id'];
        } elseif (preg_match('~^/video(-?[0-9]+_[0-9]+)$~D', $path, $match)) {
            $id = $match[1];
        } elseif (is_string($query['z'] ?? null)
            && preg_match('~^video(-?[0-9]+_[0-9]+)(?:/[^\r\n]*)?$~D', $query['z'], $match)) {
            $id = $match[1];
        }

        $hash = $query['hash'] ?? $query['access_key'] ?? '';
        if (!preg_match('/^-?[0-9]{1,20}_[0-9]{1,20}$/D', $id) || !self::validToken($hash)) {
            return null;
        }

        [$owner, $video] = explode('_', $id, 2);
        $base = str_ends_with($host, 'vkvideo.ru') ? 'https://vkvideo.ru' : 'https://vk.com';
        $embedQuery = '?oid=' . $owner . '&id=' . $video . ($hash !== '' ? '&hash=' . $hash : '');

        return [
            'provider' => 'vk',
            'id' => $id,
            'embed' => $base . '/video_ext.php' . $embedQuery,
            'url' => $base . '/video' . $id . ($hash !== '' ? '?hash=' . $hash : ''),
        ];
    }

    private static function validToken(mixed $value): bool
    {
        return is_string($value) && preg_match('/^[A-Za-z0-9_-]{0,160}$/D', $value) === 1;
    }
}
