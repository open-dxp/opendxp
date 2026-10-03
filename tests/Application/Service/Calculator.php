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


namespace OpenDxp\Tests\Application\Service;

use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Model\DataObject\ClassDefinition\CalculatorClassInterface;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Data\CalculatedValue;

final class Calculator implements CalculatorClassInterface
{
    public const string VALUE_KEY = 'modeltest.testCalculatedValue.value';

    public function compute(Concrete $object, CalculatedValue $context): string
    {
        return RuntimeCache::isRegistered(self::VALUE_KEY) ? RuntimeCache::get(self::VALUE_KEY) : '';
    }

    public function getCalculatedValueForEditMode(Concrete $object, CalculatedValue $context): string
    {
        return $this->compute($object, $context);
    }
}
