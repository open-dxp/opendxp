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

namespace OpenDxp\Tests\Support\Test;

use Codeception\Test\Unit;
use OpenDxp;
use OpenDxp\Tests\Support\Helper\DataType\Calculator;
use OpenDxp\Tests\Support\Util\TestHelper;

/**
 * @deprecated since OpenDXP 1.5 and will be removed in 2.0
 */
abstract class TestCase extends Unit
{
    protected bool $cleanupDbInSetup = true;

    /**
     * Determine if the test needs a DB connection (will be skipped if no DB is present)
     */
    protected function needsDb(): bool
    {
        return false;
    }

    protected function setUp(): void
    {
        parent::setUp();

        OpenDxp::getContainer()->set('test.calculatorservice', new Calculator());

        if ($this->needsDb()) {
            TestHelper::checkDbSupport();

            // every single test assumes a clean database
            if ($this->cleanupDbInSetup) {
                TestHelper::cleanUp();
            }
        }

        OpenDxp::collectGarbage();
    }
}
