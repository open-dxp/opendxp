<?php

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

namespace OpenDxp\Bundle\UuidBundle;

use OpenDxp\Extension\Bundle\Installer\SettingsStoreAwareInstaller;

class Installer extends SettingsStoreAwareInstaller
{
    #[\Override]
    public function install(): void
    {
        $this->installDatabaseTable();
        parent::install();
    }

    #[\Override]
    public function uninstall(): void
    {
        $this->uninstallDatabaseTable();
        parent::uninstall();
    }

    private function runSqlQueries(array $sqlFileNames): void
    {
        $sqlPath = __DIR__ . '/Resources/';
        $db = \OpenDxp\Db::get();

        foreach ($sqlFileNames as $fileName) {
            $statement = file_get_contents($sqlPath.$fileName);
            $db->executeQuery($statement);
        }
    }

    protected function installDatabaseTable(): void
    {
        $this->runSqlQueries(['install/install.sql']);
    }

    protected function uninstallDatabaseTable(): void
    {
        $this->runSqlQueries(['uninstall/uninstall.sql']);
    }
}
