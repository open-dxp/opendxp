<?php
declare(strict_types=1);

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\ApplicationLoggerBundle;

use OpenDxp\Extension\Bundle\Installer\SettingsStoreAwareInstaller;

class Installer extends SettingsStoreAwareInstaller
{
    protected const USER_PERMISSIONS_CATEGORY = 'OpenDxp Application Logger Bundle';

    protected const USER_PERMISSIONS = [
        'application_logging',
    ];

    #[\Override]
    public function install(): void
    {
        $this->addUserPermission();
        $this->createApplicationLogTable();

        parent::install();
    }

    #[\Override]
    public function uninstall(): void
    {
        $this->removeUserPermission();
        $this->dropApplicationLogTable();

        parent::uninstall();
    }

    private function createApplicationLogTable(): void
    {
        $db = \OpenDxp\Db::get();

        $db->executeQuery("CREATE TABLE IF NOT EXISTS `application_logs` (
          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
          `pid` INT(11) NULL DEFAULT NULL,
          `timestamp` datetime NOT NULL,
          `message` TEXT NULL,
          `priority` ENUM('emergency','alert','critical','error','warning','notice','info','debug') DEFAULT NULL,
          `fileobject` varchar(1024) DEFAULT NULL,
          `info` varchar(1024) DEFAULT NULL,
          `component` varchar(190) DEFAULT NULL,
          `source` varchar(190) DEFAULT NULL,
          `relatedobject` int(11) unsigned DEFAULT NULL,
          `relatedobjecttype` enum('object','document','asset') DEFAULT NULL,
          `maintenanceChecked` tinyint(1) DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `component` (`component`),
          KEY `timestamp` (`timestamp`),
          KEY `relatedobject` (`relatedobject`),
          KEY `priority` (`priority`),
          KEY `maintenanceChecked` (`maintenanceChecked`)
        ) DEFAULT CHARSET=utf8mb4;");
    }

    private function dropApplicationLogTable(): void
    {
        $db = \OpenDxp\Db::get();

        $db->executeQuery('DROP TABLE IF EXISTS `application_logs`;');
    }

    private function addUserPermission(): void
    {
        $db = \OpenDxp\Db::get();

        foreach (self::USER_PERMISSIONS as $permission) {
            $db->insert('users_permission_definitions', [
                $db->quoteIdentifier('key') => $permission,
                $db->quoteIdentifier('category') => self::USER_PERMISSIONS_CATEGORY,
            ]);
        }
    }

    private function removeUserPermission(): void
    {
        $db = \OpenDxp\Db::get();

        foreach (self::USER_PERMISSIONS as $permission) {
            $db->delete('users_permission_definitions', [
                $db->quoteIdentifier('key') => $permission,
            ]);
        }
    }
}
