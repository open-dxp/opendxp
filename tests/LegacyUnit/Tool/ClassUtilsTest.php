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

namespace OpenDxp\Tests\Unit\Tool;

use OpenDxp\Tests\Support\Test\TestCase;
use OpenDxp\Tool\ClassUtils;
use SplFileInfo;

class ClassUtilsTest extends TestCase
{
    public function testFindClassName(): void
    {
        $file = new SplFileInfo(__FILE__);
        $className = ClassUtils::findClassName($file);

        $this->assertEquals($className, self::class);
    }

    public function testFindNamespaceClassName(): void
    {
        //find classname for DummyNamespace/ClassX
        $file = new SplFileInfo(__DIR__ . '/../../Support/Resources/dummyfiles/ClassX.php');
        $className = ClassUtils::findClassName($file);

        $this->assertEquals('DummyNamespace\\ClassX', $className);

        //find classname for DummyNamespace/ClassY
        $file = new SplFileInfo(__DIR__ . '/../../Support/Resources/dummyfiles/ClassY.php');
        $className = ClassUtils::findClassName($file);

        $this->assertEquals('OpenDxp\\DummyNamespace\\ClassY', $className);
    }
}
