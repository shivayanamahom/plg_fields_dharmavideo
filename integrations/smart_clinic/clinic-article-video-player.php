<?php

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;

?>
<div class="clinic-article-hero-video ratio ratio-16x9 rounded-4 overflow-hidden">
    <?= LayoutHelper::render('plg_fields_dharmavideo.launch', $displayData, JPATH_PLUGINS . '/fields/dharmavideo/layouts') ?>
</div>
