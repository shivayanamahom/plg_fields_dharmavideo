<?php

declare(strict_types=1);

use Dharma\Plugin\Fields\Dharmavideo\Extension\Dharmavideo;
use Dharma\Plugin\Fields\Dharmavideo\Helper\VideoPoster;
use Dharma\Plugin\Fields\Dharmavideo\Helper\VideoProvider;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;

defined('_JEXEC') or die;

$app = Factory::getApplication();

if (!$app->isClient('site') || !($app->getDocument() instanceof HtmlDocument)
    || $app->getInput()->getCmd('option') !== 'com_content' || $app->getInput()->getCmd('view') !== 'article'
    || (int) $app->getInput()->getInt('id') !== (int) ($item->id ?? 0)) {
    return;
}

$rows = is_string($field->value) ? json_decode($field->value, true) : $field->value;
$videos = [];

foreach (array_slice((array) $rows, 0, Dharmavideo::MAX_ROWS) as $row) {
    $row = (array) $row;
    if ($video = VideoProvider::parse($row['url'] ?? null)) {
        $title = $row['title'] ?? '';
        $video['title'] = is_string($title) ? trim($title) : '';
        $video['poster'] = VideoPoster::cached($video);
        $videos[] = $video;
    }
}

if ($videos === []) {
    return;
}

$field->dharmavideoItems = $videos;

echo LayoutHelper::render('plg_fields_dharmavideo.gallery', [
    'videos' => $videos,
    'itemId' => (int) $item->id,
    'fieldId' => (int) $field->id,
    'playerMode' => $fieldParams->get('player_mode', 'inline'),
], JPATH_PLUGINS . '/fields/dharmavideo/layouts');
