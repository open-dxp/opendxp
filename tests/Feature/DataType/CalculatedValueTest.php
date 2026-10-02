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
use OpenDxp\Db;
use OpenDxp\Tests\Factory\UnittestFactory;

it('hands back what the calculator service worked out', function () {

    $object = UnittestFactory::createOne();

    // The calculator of the test application reads the value out of the runtime cache.
    $value = uniqid();
    RuntimeCache::set('modeltest.testCalculatedValue.value', $value);

    expect($object->getCalculatedValue())->toBe($value);
});

it('works a value out of an expression and writes it into the query table', function () {

    $object = UnittestFactory::createOne();
    $object->setFirstname('Jane');

    expect($object->getCalculatedValueExpression())->toBe('Jane some calc');

    $object->save();

    $written = Db::get()->fetchOne(sprintf(
        'SELECT calculatedValueExpression FROM object_query_%s WHERE oo_id = %d',
        $object->getClassId(),
        $object->getId(),
    ));

    expect($written)->toBe('Jane some calc');
});

it('keeps a constant out of an expression', function () {

    expect(UnittestFactory::createOne()->getCalculatedValueExpressionConstant())
        ->not->toBe(OPENDXP_PROJECT_ROOT);
});
