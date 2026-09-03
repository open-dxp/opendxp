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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\ApplicationLoggerBundle;

use OpenDxp\Bundle\ApplicationLoggerBundle\Handler\ApplicationLoggerDb;
use OpenDxp\Bundle\ApplicationLoggerBundle\Schema\ApplicationLogSchema;
use OpenDxp\Bundle\ApplicationLoggerBundle\Security\ApplicationLoggerPermission;
use OpenDxp\Extension\Bundle\Installer\SettingsStoreAwareInstaller;
use OpenDxp\Security\PermissionAttribute;
use Override;

class Installer extends SettingsStoreAwareInstaller
{
    protected const string USER_PERMISSIONS_CATEGORY = 'OpenDxp Application Logger Bundle';

    #[Override]
    public function install(): void
    {
        $this->addUserPermission();
        $this->createApplicationLogTable();

        parent::install();
    }

    #[Override]
    public function uninstall(): void
    {
        $this->removeUserPermission();
        $this->dropApplicationLogTable();

        parent::uninstall();
    }

    private function createApplicationLogTable(): void
    {
        $db = \OpenDxp\Db::get();

        $db->executeQuery(ApplicationLogSchema::createLogTable(
            $db->quoteIdentifier(ApplicationLoggerDb::TABLE_NAME)
        ));
    }

    private function dropApplicationLogTable(): void
    {
        $db = \OpenDxp\Db::get();

        $db->executeQuery('DROP TABLE IF EXISTS `application_logs`;');
    }

    private function addUserPermission(): void
    {
        $db = \OpenDxp\Db::get();

        foreach (ApplicationLoggerPermission::cases() as $permission) {
            $db->insert('users_permission_definitions', [
                $db->quoteIdentifier('key') => PermissionAttribute::for($permission->value),
                $db->quoteIdentifier('category') => self::USER_PERMISSIONS_CATEGORY,
            ]);
        }
    }

    private function removeUserPermission(): void
    {
        $db = \OpenDxp\Db::get();

        foreach (ApplicationLoggerPermission::cases() as $permission) {
            $db->delete('users_permission_definitions', [
                $db->quoteIdentifier('key') => PermissionAttribute::for($permission->value),
            ]);
        }
    }
}
