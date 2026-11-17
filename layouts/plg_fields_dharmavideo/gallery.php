<?php

declare(strict_types=1);

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

defined('_JEXEC') or die;

$videos = $displayData['videos'];
if ($videos === []) {
    return;
}

$smColumns = min(2, count($videos));
$lgColumns = min(3, count($videos));

$escape = static fn (string $text): string => htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<ul class="dharmavideo-list list-unstyled mb-0 row row-cols-1 row-cols-sm-<?= $smColumns ?> row-cols-lg-<?= $lgColumns ?> g-3">
    <?php foreach ($videos as $index => $video) :
        $provider = $video['provider'] === 'vk' ? 'VK Видео' : 'Rutube';
        ?>
        <li class="dharmavideo-item col">
            <figure class="mb-0">
                <?= LayoutHelper::render('plg_fields_dharmavideo.launch', [
                    'video' => $video,
                    'playerMode' => $displayData['playerMode'],
                    'group' => 'dharmavideo-' . $displayData['itemId'] . '-' . $displayData['fieldId'] . '-' . $index,
                ], __DIR__ . '/..') ?>
                <figcaption class="d-flex flex-wrap justify-content-between gap-2 mt-2 small">
                    <?php if ($video['title'] !== '') : ?>
                        <span><?= $escape($video['title']) ?></span>
                    <?php endif; ?>
                    <a href="<?= $escape($video['url']) ?>" target="_blank" rel="noopener noreferrer"><?= $escape(Text::sprintf('PLG_FIELDS_DHARMAVIDEO_OPEN', $provider)) ?></a>
                </figcaption>
            </figure>
        </li>
    <?php endforeach; ?>
</ul>
