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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261012120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove the Studio flag of the notifications';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('notifications') || !$schema->getTable('notifications')->hasColumn('isStudio')) {
            return;
        }

        $this->addSql('DELETE FROM `notifications` WHERE `isStudio` = 1');
        $this->addSql('ALTER TABLE `notifications` DROP COLUMN `isStudio`');
    }

    public function down(Schema $schema): void
    {
    }
}
