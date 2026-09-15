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

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Utils\Constants\TableConstants;

final class Version20260914214231 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        if (!$schema->hasTable(TableConstants::JOB_RUN_TABLE)) {
            return;
        }

        $this->addSql(sprintf('ALTER TABLE %s MODIFY context JSON NULL', TableConstants::JOB_RUN_TABLE));
    }
}
