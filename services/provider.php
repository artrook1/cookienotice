<?php

/**
 * @package     plg_system_cookienotice
 * @copyright   (C) 2026 artrook1
 * @license     GNU General Public License version 2 or later
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Plugin\System\Cookienotice\Extension\Cookienotice;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            $container->lazy(Cookienotice::class, function (Container $container) {
                $plugin = new Cookienotice(
                    (array) PluginHelper::getPlugin('system', 'cookienotice')
                );
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            })
        );
    }
};
