<?php

declare(strict_types=1);

namespace Dharma\Plugin\Fields\Dharmavideo\Field;

use Dharma\Plugin\Fields\Dharmavideo\Extension\Dharmavideo;
use Dharma\Plugin\Fields\Dharmavideo\Helper\VideoProvider;
use Joomla\CMS\Form\Field\SubformField;

defined('_JEXEC') or die;

final class DharmavideoField extends SubformField
{
    protected $type = 'Dharmavideo';

    public function setup(\SimpleXMLElement $element, $value, $group = null)
    {
        if (!parent::setup($element, $value, $group)) {
            return false;
        }

        $disabled = $this->disabled || $this->readonly ? 'true' : 'false';
        $maxUrl = VideoProvider::MAX_URL;
        $this->multiple = true;
        $this->__set('fieldname', 'row');
        $this->__set('min', $this->required ? 1 : 0);
        $this->__set('max', Dharmavideo::MAX_ROWS);
        $this->__set('layout', 'joomla.form.field.subform.repeatable');
        $this->__set('buttons', 'add,remove,move');
        $this->__set('formsource', <<<XML
            <form addfieldprefix="Dharma\Plugin\Fields\Dharmavideo\Field">
                <field name="url" type="dharmavideourl" required="true" disabled="$disabled"
                    label="PLG_FIELDS_DHARMAVIDEO_URL" description="PLG_FIELDS_DHARMAVIDEO_URL_DESC" maxlength="$maxUrl" filter="raw" />
                <field name="title" type="text" disabled="$disabled"
                    label="PLG_FIELDS_DHARMAVIDEO_TITLE" maxlength="180" filter="string" />
            </form>
            XML);

        return true;
    }

    protected function getInput()
    {
        // Read-only field values must not produce editable rows or a clearing input.
        if ($this->disabled || $this->readonly) {
            $links = [];

            foreach ((array) $this->value as $row) {
                if ($video = VideoProvider::parse(((array) $row)['url'] ?? null)) {
                    $links[] = '<a href="' . htmlspecialchars($video['url'], ENT_QUOTES, 'UTF-8')
                        . '" target="_blank" rel="noopener noreferrer">'
                        . htmlspecialchars($video['url'], ENT_QUOTES, 'UTF-8') . '</a>';
                }
            }

            return implode('<br>', $links);
        }

        return parent::getInput();
    }
}
