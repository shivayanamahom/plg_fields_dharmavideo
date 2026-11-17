<?php

declare(strict_types=1);

namespace Dharma\Plugin\Fields\Dharmavideo\Field;

use Dharma\Plugin\Fields\Dharmavideo\Helper\VideoProvider;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\TextField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

final class DharmavideourlField extends TextField
{
    protected $type = 'Dharmavideourl';
    protected $hiddenDescription = true;

    protected function getInput()
    {
        $descriptionId = htmlspecialchars(($this->id ?: $this->name) . '-desc', ENT_QUOTES, 'UTF-8');
        $help = htmlspecialchars(Text::_('PLG_FIELDS_DHARMAVIDEO_URL_DESC'), ENT_QUOTES, 'UTF-8');
        $html = parent::getInput() . '<div id="' . $descriptionId . '" class="form-text" data-dharmavideo-help>' . $help . '</div>';
        $app = Factory::getApplication();

        if (!$app->isClient('administrator') || !($app->getDocument() instanceof HtmlDocument)) {
            return $html;
        }

        $doc = $app->getDocument();
        $doc->addScriptOptions('plg_fields_dharmavideo.preview', [
            'url' => Uri::base() . 'index.php?option=com_ajax&group=fields&plugin=dharmavideoPreview&format=json',
            'token' => Session::getFormToken(),
            'error' => Text::_('PLG_FIELDS_DHARMAVIDEO_PREVIEW_ERROR'),
            'title' => Text::_('PLG_FIELDS_DHARMAVIDEO_PREVIEW'),
        ]);
        $wa = $doc->getWebAssetManager();
        $wa->registerAndUseScript('plg_fields_dharmavideo.preview', 'plg_fields_dharmavideo/preview.js', ['version' => 'auto'], ['defer' => true], ['core']);
        $wa->registerAndUseStyle('plg_fields_dharmavideo.player', 'plg_fields_dharmavideo/player.css', ['version' => 'auto']);

        $video = VideoProvider::parse($this->value);
        $embedAttribute = $video === null ? '' : ' data-dharmavideo-initial-embed="'
            . htmlspecialchars($video['embed'], ENT_QUOTES, 'UTF-8') . '"';

        return '<div class="dharmavideo-editor" data-dharmavideo-editor' . $embedAttribute . '>' . $html
            . '<div role="status" data-dharmavideo-status></div><div data-dharmavideo-player></div></div>';
    }
}
