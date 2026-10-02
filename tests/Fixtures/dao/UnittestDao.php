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

namespace OpenDxp\Model\DataObject\Unittest;

use OpenDxp\Model\DataObject\Concrete;

/**
 * Test-only stand-in for a project-specific DataObject DAO that overrides
 * getById() with custom logic (see CustomDaoGetByIdTest). Deliberately not
 * autoloadable: the test require's it explicitly, and the regular DAO class
 * detection then picks it up for the Unittest model class.
 */
class Dao extends Concrete\Dao
{
    /**
     * @var int[]
     */
    public static array $getByIdCalls = [];

    public function getById(int $id): void
    {
        self::$getByIdCalls[] = $id;

        parent::getById($id);
    }
}
