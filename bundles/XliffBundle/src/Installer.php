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

namespace OpenDxp\Bundle\XliffBundle;

use OpenDxp\Extension\Bundle\Installer\SettingsStoreAwareInstaller;
use Override;

/**
 * @internal
 */
class Installer extends SettingsStoreAwareInstaller
{
    protected const USER_PERMISSION_CATEGORY = 'OpenDxp Xliff Bundle';

    protected const USER_PERMISSIONS = [
        'xliff_import_export',
    ];

    #[Override]
    public function install(): void
    {
        $this->addUserPermission();
        parent::install();
    }

    #[Override]
    public function uninstall(): void
    {
        $this->removeUserPermission();
        parent::uninstall();
    }

    private function addUserPermission(): void
    {
        $db = \OpenDxp\Db::get();

        foreach (self::USER_PERMISSIONS as $permission) {
            $db->insert('users_permission_definitions', [
                $db->quoteIdentifier('key') => $permission,
                $db->quoteIdentifier('category') => self::USER_PERMISSION_CATEGORY,
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
