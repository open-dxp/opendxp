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

use OpenDxp\Db;
use OpenDxp\Model\DataObject\Concrete;

/**
 * The query table holds what a listing filters on, which can differ from what the getter returns.
 */
function queryTableValue(Concrete $object, string $column): mixed
{
    return Db::get()->fetchOne(
        sprintf('SELECT `%s` FROM object_query_%s WHERE oo_id = ?', $column, $object->getClassId()),
        [$object->getId()],
    );
}
