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

namespace OpenDxp\Tests\Feature\DataType;

use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Tests\Application\Service\Calculator;
use OpenDxp\Tests\Factory\UnittestFactory;

it('returns what the calculator service works out', function () {
    $object = UnittestFactory::createOne();
    RuntimeCache::set(Calculator::VALUE_KEY, 'the calculated value');

    $value = $object->getCalculatedValue();

    expect($value)->toBe('the calculated value');
});

it('works a value out of an expression', function () {
    $object = UnittestFactory::createOne();

    $object->setFirstname('Jane');

    expect($object->getCalculatedValueExpression())->toBe('Jane some calc');
});

it('writes the value of an expression into the query table', function () {
    $object = UnittestFactory::createOne();

    $object->setFirstname('Jane');
    $object->save();

    expect(queryTableValue($object, 'calculatedValueExpression'))->toBe('Jane some calc');
});

it('refuses to read a constant in an expression', function () {
    $object = UnittestFactory::createOne();

    $value = $object->getCalculatedValueExpressionConstant();

    expect($value)->toBe('`constant` function not available around position 0.');
});
