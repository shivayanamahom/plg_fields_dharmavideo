<?php

declare(strict_types=1);

namespace Dharma\Plugin\Fields\Dharmavideo\Rule;

use Dharma\Plugin\Fields\Dharmavideo\Extension\Dharmavideo;
use Dharma\Plugin\Fields\Dharmavideo\Helper\VideoProvider;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormRule;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

final class DharmavideoRule extends FormRule
{
    public function test(\SimpleXMLElement $element, $value, $group = null, ?Registry $input = null, ?Form $form = null)
    {
        if ($value instanceof \stdClass) {
            $value = (array) $value;
        }

        if ($value === null || $value === [] || $value === '') {
            return (string) $element['required'] !== 'true';
        }

        if (!is_array($value) || count($value) > Dharmavideo::MAX_ROWS) {
            throw new \UnexpectedValueException(Text::sprintf('PLG_FIELDS_DHARMAVIDEO_MAX_ROWS', Dharmavideo::MAX_ROWS));
        }

        $position = 0;
        foreach ($value as $row) {
            ++$position;
            if (VideoProvider::parse(((array) $row)['url'] ?? null) === null) {
                throw new \UnexpectedValueException(Text::sprintf('PLG_FIELDS_DHARMAVIDEO_INVALID_ROW', $position));
            }
        }

        return true;
    }
}
