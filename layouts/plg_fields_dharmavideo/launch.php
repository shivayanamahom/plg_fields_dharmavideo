<?php

declare(strict_types=1);

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

$app = Factory::getApplication();
$wa = $app->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('plg_fields_dharmavideo.player', 'plg_fields_dharmavideo/player.css', ['version' => 'auto']);
$useFancybox = $displayData['playerMode'] === 'fancybox' && $wa->assetExists('preset', 'wtfancybox');

if ($useFancybox) {
    $wa->usePreset('wtfancybox');
}

$wa->registerAndUseScript('plg_fields_dharmavideo.player', 'plg_fields_dharmavideo/player.js', ['version' => 'auto'], ['defer' => true]);

$video = $displayData['video'];
$provider = $video['provider'] === 'vk' ? 'VK Видео' : 'Rutube';
$title = $video['title'] !== '' ? $video['title'] : Text::sprintf('PLG_FIELDS_DHARMAVIDEO_PLAYER_TITLE', $provider);
$playLabel = Text::sprintf('PLG_FIELDS_DHARMAVIDEO_PLAY', $title);
$embed = $video['embed'] . (str_contains($video['embed'], '?') ? '&' : '?')
    . 'autoplay=' . ($video['provider'] === 'vk' ? '1' : 'true');
$escape = static fn (string $text): string => htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<a class="dharmavideo-launch ratio ratio-16x9 rounded overflow-hidden text-white bg-dark"
    href="<?= $escape($video['url']) ?>" target="_blank" rel="noopener noreferrer"
    aria-label="<?= $escape($playLabel) ?>"
    data-dharmavideo-embed="<?= $escape($video['embed']) ?>"
    data-dharmavideo-title="<?= $escape($title) ?>"
    data-dharmavideo-provider="<?= $escape($video['provider']) ?>"
    <?php if ($useFancybox) : ?>
        data-dharmavideo-fancybox
        data-fancybox="<?= $escape($displayData['group']) ?>"
        data-type="iframe" data-src="<?= $escape($embed) ?>"
        data-width="960" data-height="540"
    <?php endif; ?>>
    <?php if ($video['poster'] !== '') : ?>
        <img src="<?= $escape(Uri::root(true) . '/' . $video['poster']) ?>" width="640" height="360"
            loading="lazy" class="object-fit-cover" alt="" data-lowvision-decorative>
    <?php endif; ?>
    <span class="d-flex align-items-center justify-content-center" aria-hidden="true">
        <svg class="dharmavideo-play rounded-circle p-3" viewBox="0 0 24 24" width="56" height="56" focusable="false">
            <path fill="currentColor" d="M8 5v14l12-7z" />
        </svg>
    </span>
</a>
