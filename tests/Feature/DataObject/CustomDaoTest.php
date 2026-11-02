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

use OpenDxp\Model\AbstractModel;
use OpenDxp\Model\DataObject\TestFieldTypes;
use OpenDxp\Test\Elements;
use OpenDxp\Tests\Factory\TestFieldTypesFactory;
use ReflectionClass;

beforeEach(function () {
    // Dao detection walks up the namespace of a model class. Once the fixture exists, it would become the dao of
    // every class under TestFieldTypes, such as its bricks. Loading an object resolves their daos before that.
    Elements::refresh(TestFieldTypesFactory::createOne());

    $this->resolvedDaos = (new ReflectionClass(AbstractModel::class))->getStaticPropertyValue('daoClassCache');

    // The class lives in the namespace of OpenDXP, so no autoload rule of the test suite reaches it.
    require_once fixture('dao/TestFieldTypesDao.php');

    (new TestFieldTypes())->initDao(forceDetection: true);
});

afterEach(function () {
    (new ReflectionClass(AbstractModel::class))->setStaticPropertyValue('daoClassCache', $this->resolvedDaos);
    TestFieldTypes\Dao::$getByIdCalls = [];
});

it('loads an object through the dao a project writes for its class', function () {
    $object = TestFieldTypesFactory::createOne(['input' => 'loaded by the project dao']);

    $loaded = Elements::refresh($object);

    expect($loaded->getInput())
        ->toBe('loaded by the project dao')
        ->and(TestFieldTypes\Dao::$getByIdCalls)
        ->toBe([$object->getId()]);
});
