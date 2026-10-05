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

namespace OpenDxp\Tests\Support\Helper\DataType;

use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Model\DataObject\ClassDefinition\CalculatorClassInterface;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Data\CalculatedValue;

/**
 * @deprecated since OpenDXP 1.5 and will be removed in 2.0
 */
class Calculator implements CalculatorClassInterface
{
    public function compute(Concrete $object, CalculatedValue $context): string
    {
        $value = '';
        if (RuntimeCache::isRegistered('modeltest.testCalculatedValue.value')) {
            $value = RuntimeCache::get('modeltest.testCalculatedValue.value');
        }

        return $value;
    }

    public function getCalculatedValueForEditMode(Concrete $object, CalculatedValue $context): string
    {
    }
}
