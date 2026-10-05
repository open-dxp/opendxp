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

namespace OpenDxp\Tests\TestCase;

use DAMA\DoctrineTestBundle\PHPUnit\SkipDatabaseRollback;
use OpenDxp\TestFoundation\TestCase;

// InnoDB writes a full text index only on commit, so a test that searches cannot run in a transaction.
#[SkipDatabaseRollback]
abstract class SearchTestCase extends TestCase
{
}
