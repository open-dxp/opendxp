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

namespace OpenDxp\Bundle\SeoBundle;

use OpenDxp\Bundle\SeoBundle\Security\SeoPermission;
use OpenDxp\Cache;
use OpenDxp\Extension\Bundle\Installer\SettingsStoreAwareInstaller;
use OpenDxp\Security\PermissionAttribute;
use Override;

class Installer extends SettingsStoreAwareInstaller
{
    protected const string USER_PERMISSIONS_CATEGORY = 'OpenDxp Seo Bundle';

    #[Override]
    public function install(): void
    {
        $this->installDatabaseTable();
        $this->addUserPermission();
        parent::install();
        Cache::clearTag('redirect');
    }

    #[Override]
    public function uninstall(): void
    {
        $this->uninstallDatabaseTable();
        $this->removeUserPermission();
        parent::uninstall();
        Cache::clearTag('redirect');
    }

    private function addUserPermission(): void
    {
        $db = \OpenDxp\Db::get();

        foreach (SeoPermission::cases() as $permission) {
            $db->insert('users_permission_definitions', [
                $db->quoteIdentifier('key') => PermissionAttribute::for($permission->value),
                $db->quoteIdentifier('category') => self::USER_PERMISSIONS_CATEGORY,
            ]);
        }
    }

    private function removeUserPermission(): void
    {
        $db = \OpenDxp\Db::get();

        foreach (SeoPermission::cases() as $permission) {
            $db->delete('users_permission_definitions', [
                $db->quoteIdentifier('key') => PermissionAttribute::for($permission->value),
            ]);
        }
    }

    private function installDatabaseTable(): void
    {
        $sqlPath = __DIR__ . '/Resources/install/';
        $sqlFileNames = ['install.sql'];
        $db = \OpenDxp\Db::get();

        foreach ($sqlFileNames as $fileName) {
            $statement = file_get_contents($sqlPath.$fileName);
            $db->executeQuery($statement);
        }
    }

    private function uninstallDatabaseTable(): void
    {
        $sqlPath = __DIR__ . '/Resources/uninstall/';
        $sqlFileNames = ['uninstall.sql'];
        $db = \OpenDxp\Db::get();

        foreach ($sqlFileNames as $fileName) {
            $statement = file_get_contents($sqlPath.$fileName);
            $db->executeQuery($statement);
        }
    }
}
