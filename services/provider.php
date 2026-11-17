<?php

declare(strict_types=1);

use Dharma\Plugin\Fields\Dharmavideo\Extension\Dharmavideo;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

defined('_JEXEC') or die;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(PluginInterface::class, static function (): PluginInterface {
            $plugin = new Dharmavideo((array) PluginHelper::getPlugin('fields', 'dharmavideo'));
            $plugin->setApplication(Factory::getApplication());

            return $plugin;
        });
    }
};
