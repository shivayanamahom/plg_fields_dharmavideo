<?php

declare(strict_types=1);

use Dharma\Plugin\Fields\Dharmavideo\Helper\VideoProvider;

define('_JEXEC', 1);
require dirname(__DIR__) . '/src/Helper/VideoProvider.php';

$id = '7716bd3e665725c3c008ae7ab4ff02e2';
$cases = [
    [' https://rutube.ru/video/' . $id . '/?p=Private-Key&from=share ', 'https://rutube.ru/play/embed/' . $id . '/?p=Private-Key'],
    ['https://rutube.ru/video/private/' . $id . '/?p=Private-Key', 'https://rutube.ru/play/embed/' . $id . '/?p=Private-Key'],
    ['https://rutube.ru/video/private/' . $id . '/', null],
    ['rutube.ru/shorts/' . $id, 'https://rutube.ru/play/embed/' . $id . '/'],
    ['https://rutube.ru/play/embed/' . $id, 'https://rutube.ru/play/embed/' . $id . '/'],
    ['https://vkvideo.ru/video-235261016_456239036?list=foo', 'https://vkvideo.ru/video_ext.php?oid=-235261016&id=456239036'],
    ['https://vk.com/video_ext.php?oid=-123&id=456&hash=Hash_1&hd=2', 'https://vk.com/video_ext.php?oid=-123&id=456&hash=Hash_1'],
    ['https://vk.com/wall-1_2?z=video-123_456%2Fpl_wall-1', 'https://vk.com/video_ext.php?oid=-123&id=456'],
    ['https://vk.com.evil.test/video-123_456', null],
    ['https://evil.test@vk.com/video-123_456', null],
    ['https://vk.com:1234/video-123_456', null],
    ['ftp://vk.com/video-123_456', null],
    ['https://vk.com/video_ext.php?oid[]=1&id=2', null],
    ['https://vk.com/video-123_456?hash[]=1', null],
    ['https://rutube.ru/video/' . $id . '/?p=%26autoplay%3D1', null],
    ['https://rutube.ru/video/' . $id . '0', null],
    ['https://rutube.ru/video/' . $id . '/extra', null],
    ['<iframe src="https://vk.com/video-123_456"></iframe>', null],
    [['url' => 'https://vk.com/video-123_456'], null],
];

foreach ($cases as $i => [$url, $embed]) {
    if ((VideoProvider::parse($url)['embed'] ?? null) !== $embed) {
        throw new RuntimeException('Unexpected embed URL for case ' . ($i + 1));
    }
}

echo count($cases) . " cases passed\n";
