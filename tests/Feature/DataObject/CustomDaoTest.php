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


namespace OpenDxp\Tests\Feature\DataObject;

use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Model\AbstractModel;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\Unittest;
use OpenDxp\Tests\Factory\UnittestFactory;
use ReflectionProperty;

it('keeps calling a dao a project wrote for one of its classes', function () {

    // Loading an object resolves the dao of every class below Unittest. That has to happen before
    // the fixture exists, or the detection hands the fixture to those classes as well.
    DataObject::getById(UnittestFactory::createOne()->getId(), ['force' => true]);

    // The class lives in OpenDXP's own namespace, so no autoload rule of the test suite reaches it.
    require_once fixture('dao/UnittestDao.php');

    $cache = new ReflectionProperty(AbstractModel::class, 'daoClassCache');
    $resolved = $cache->getValue();
    unset($resolved[Unittest::class]);
    $cache->setValue(null, $resolved);

    $object = UnittestFactory::createOne(['input' => 'custom-dao-marker']);

    expect($object->getDao())->toBeInstanceOf(Unittest\Dao::class);

    RuntimeCache::clear();
    Unittest\Dao::$getByIdCalls = [];

    $loaded = DataObject::getById($object->getId(), ['force' => true]);

    expect($loaded)
        ->toBeInstanceOf(Unittest::class)
        ->and($loaded->getInput())
        ->toBe('custom-dao-marker')
        ->and(Unittest\Dao::$getByIdCalls)
        ->toContain($object->getId());
});
