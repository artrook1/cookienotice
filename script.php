<?php

/**
 * @package     plg_system_cookienotice
 * @copyright   (C) 2026 artrook1
 * @license     GNU General Public License version 2 or later
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

/**
 * Installer: migrate settings from legacy plg_system_btlcookies when present.
 */
class PlgSystemCookienoticeInstallerScript extends InstallerScript
{
    protected $minimumPhp = '8.1.0';

    protected $minimumJoomla = '5.0.0';

    public function postflight($type, $parent): bool
    {
        if (!in_array($type, ['install', 'update'], true)) {
            return true;
        }

        $this->migrateFromBtlcookies();
        $this->enablePlugin();

        return true;
    }

    private function migrateFromBtlcookies(): void
    {
        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            $legacy = $this->loadPluginRow($db, 'btlcookies');
            $current = $this->loadPluginRow($db, 'cookienotice');

            if (!$legacy || !$current) {
                return;
            }

            $legacyParams  = new Registry($legacy->params ?: '{}');
            $currentParams = new Registry($current->params ?: '{}');

            // Only copy when the new plugin still has empty/default-looking message.
            $currentMsg = trim((string) $currentParams->get('warningMessage', ''));
            $legacyMsg  = trim((string) $legacyParams->get('warningMessage', ''));

            if ($legacyMsg !== '' && ($currentMsg === '' || !(int) $currentParams->get('migrated_from_btlcookies', 0))) {
                $currentParams->set('warningMessage', $legacyParams->get('warningMessage'));
                $currentParams->set('detailsUrl', $legacyParams->get('detailsUrl', 'privacy-policy'));
                $currentParams->set('migrated_from_btlcookies', 1);

                $update = $db->getQuery(true)
                    ->update($db->quoteName('#__extensions'))
                    ->set($db->quoteName('params') . ' = ' . $db->quote($currentParams->toString()))
                    ->where($db->quoteName('extension_id') . ' = ' . (int) $current->extension_id);
                $db->setQuery($update);
                $db->execute();
            }

            // Disable legacy plugin so only one banner shows.
            $disable = $db->getQuery(true)
                ->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('enabled') . ' = 0')
                ->where($db->quoteName('extension_id') . ' = ' . (int) $legacy->extension_id);
            $db->setQuery($disable);
            $db->execute();
        } catch (\Throwable) {
            // Best-effort migration.
        }
    }

    private function enablePlugin(): void
    {
        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('enabled') . ' = 1')
                ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('cookienotice'));
            $db->setQuery($query);
            $db->execute();
        } catch (\Throwable) {
            // Admin can enable manually.
        }
    }

    private function loadPluginRow(DatabaseInterface $db, string $element): ?object
    {
        $query = $db->getQuery(true)
            ->select($db->quoteName(['extension_id', 'params', 'enabled']))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
            ->where($db->quoteName('element') . ' = ' . $db->quote($element));
        $db->setQuery($query);

        $row = $db->loadObject();

        return $row ?: null;
    }
}
