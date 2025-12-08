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

namespace OpenDxp\Bundle\CustomReportsBundle\Tool\Adapter;

use OpenDxp\Bundle\CustomReportsBundle\Tool\Config;
use stdClass;

abstract class AbstractAdapter implements CustomReportAdapterInterface
{
    public function __construct(protected stdClass $config, protected ?Config $fullConfig = null)
    {
    }

    abstract public function getData(?array $filters, ?string $sort, ?string $dir, ?int $offset, ?int $limit, ?array $fields = null, ?array $drillDownFilters = null): array;

    abstract public function getColumns(?stdClass $configuration): array;

    abstract public function getAvailableOptions(array $filters, string $field, array $drillDownFilters): array;
}
