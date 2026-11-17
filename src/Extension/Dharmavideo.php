<?php

declare(strict_types=1);

namespace Dharma\Plugin\Fields\Dharmavideo\Extension;

use Dharma\Plugin\Fields\Dharmavideo\Helper\VideoPoster;
use Dharma\Plugin\Fields\Dharmavideo\Helper\VideoProvider;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Event\Model\AfterSaveEvent;
use Joomla\CMS\Event\Plugin\AjaxEvent;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Component\Fields\Administrator\Plugin\FieldsPlugin;
use Joomla\Event\SubscriberInterface;

defined('_JEXEC') or die;

final class Dharmavideo extends FieldsPlugin implements SubscriberInterface
{
    public const MAX_ROWS = 20;

    public static function getSubscribedEvents(): array
    {
        return parent::getSubscribedEvents() + [
            'onAjaxDharmavideoPreview' => 'preview',
            'onContentAfterSave' => 'cacheArticlePosters',
        ];
    }

    public function onCustomFieldsPrepareDom($field, \DOMElement $parent, Form $form)
    {
        $node = parent::onCustomFieldsPrepareDom($field, $parent, $form);

        if ($node === null) {
            return null;
        }

        $node->setAttribute('addfieldprefix', 'Dharma\Plugin\Fields\Dharmavideo\Field');
        $node->setAttribute('addruleprefix', 'Dharma\Plugin\Fields\Dharmavideo\Rule');
        $node->setAttribute('validate', 'dharmavideo');

        return $node;
    }

    public function onCustomFieldsPrepareField($context, $item, $field)
    {
        if ($context !== 'com_content.article' || $field->type !== 'dharmavideo'
            || !$this->getApplication()->isClient('site')
            || !(($item->params ?? null)?->get('access-view', true) ?? true)) {
            return '';
        }

        if ($this->getApplication()->getDocument() instanceof HtmlDocument && isset($item->images)
            && ($video = VideoPoster::firstVideo($field->value))) {
            $images = VideoPoster::images($item);
            $intro = trim((string) ($images['image_intro'] ?? ''));
            $full = trim((string) ($images['image_fulltext'] ?? ''));

            if ($intro === '' && $full === '') {
                // Front-end requests only read local thumbnails.
                $poster = VideoPoster::cached($video);
                if ($poster !== '') {
                    $images['image_intro'] = $poster;
                    $images['image_fulltext'] = $poster;
                    $images['image_intro_alt'] = '';
                    $images['image_fulltext_alt'] = '';
                    $item->images = json_encode($images, JSON_UNESCAPED_UNICODE);
                    $item->dharmavideoCover = $poster;
                }
            } elseif ($intro === '' || $full === '') {
                $from = $intro === '' ? 'image_fulltext' : 'image_intro';
                $to = $intro === '' ? 'image_intro' : 'image_fulltext';
                foreach (['', '_alt', '_caption'] as $suffix) {
                    $images[$to . $suffix] = $images[$from . $suffix] ?? '';
                }
                $item->images = json_encode($images, JSON_UNESCAPED_UNICODE);
            }
        }

        return parent::onCustomFieldsPrepareField($context, $item, $field);
    }

    public function cacheArticlePosters(AfterSaveEvent $event): void
    {
        $item = $event->getItem();
        $submitted = $event->getData()['com_fields'] ?? [];
        if ($event->getContext() !== 'com_content.article' || !$this->getApplication()->isClient('administrator')
            || !$submitted) {
            return;
        }

        $deadline = microtime(true) + 5.0;

        foreach (FieldsHelper::getFields('com_content.article', $item) as $field) {
            if ($field->type !== 'dharmavideo' || !FieldsHelper::canEditFieldValue($field)) {
                continue;
            }

            foreach (array_slice((array) ($submitted[$field->name] ?? []), 0, self::MAX_ROWS) as $row) {
                if (microtime(true) >= $deadline) {
                    return;
                }

                if ($video = VideoProvider::parse(((array) $row)['url'] ?? null)) {
                    try {
                        VideoPoster::cache($video, $deadline);
                    } catch (\Throwable) {
                        // A missing thumbnail must never make the saved article fail.
                    }
                }
            }
        }
    }

    public function preview(AjaxEvent $event): void
    {
        $app = $this->getApplication();

        if (!$app->isClient('administrator') || $app->getIdentity()->guest || !$app->checkToken('post')) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        }

        $video = VideoProvider::parse($app->getInput()->post->get('url', '', 'raw'));

        if ($video === null) {
            throw new \InvalidArgumentException(Text::_('PLG_FIELDS_DHARMAVIDEO_INVALID_URL'), 400);
        }

        $event->addResult($video['embed']);
    }
}
