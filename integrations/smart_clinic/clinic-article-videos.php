<?php

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;

$item = $displayData['item'];
$heroVideoFieldId = $displayData['heroVideoFieldId'] ?? null;

foreach ($item->jcfields ?? [] as $field) :
    if ($field->type !== 'dharmavideo' || (int) $field->params->get('display', 2) !== 0) {
        continue;
    }

    $videos = trim((string) $field->value);
    if ($videos === '' || str_contains((string) $item->text, $videos)) {
        continue;
    }
    if ((int) $field->id === $heroVideoFieldId) {
        $videos = trim(LayoutHelper::render('plg_fields_dharmavideo.gallery', [
            'videos' => array_slice($field->dharmavideoItems, 1),
            'itemId' => (int) $item->id,
            'fieldId' => (int) $field->id,
            'playerMode' => $field->fieldparams->get('player_mode', 'inline'),
        ], JPATH_PLUGINS . '/fields/dharmavideo/layouts'));

        if ($videos === '') {
            continue;
        }
    }
    ?>
    <div class="dc-scope mt-4 mb-4">
        <section class="card clinic-article-videos">
            <div class="card-body p-3 p-md-4">
                <h2 class="h4 mb-3 text-break"><?= htmlspecialchars($field->title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h2>
                <?= $videos ?>
            </div>
        </section>
    </div>
<?php endforeach; ?>
