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

use Closure;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Fieldcollection;
use OpenDxp\Model\DataObject\Objectbrick\Data\TestLocalizedLazyBrick;
use OpenDxp\Model\DataObject\TestFieldTypes;
use OpenDxp\Model\DataObject\TestLazyRelations;
use OpenDxp\Test\Elements;
use OpenDxp\Tests\Factory\TestFieldTypesFactory;
use OpenDxp\Tests\Factory\TestLazyRelationsFactory;

function itemWithLocalizedText(): Fieldcollection\Data\TestFeature
{
    $item = new Fieldcollection\Data\TestFeature();
    $item->setLabel('textEN', 'en');

    return $item;
}

it('binds the fields of a version to that version', function (Concrete $object, Closure $field) {
    $object->setInput('in the version');

    $object->saveVersion();
    $version = Elements::refresh($object)->getLatestVersion()->loadData();

    expect($field($version)->getObject())->getInput()->toBe('in the version');
})->with([
    'the localized fields of an object' => [
        fn () => TestFieldTypesFactory::new()
            ->withLocalizedValues(
                'linput',
                ['en' => 'some text'],
            )
            ->create(['input' => 'in the database']),
        fn (TestFieldTypes $version) => $version->getLocalizedfields(),
    ],
    'an item of a field collection' => [
        fn () => TestFieldTypesFactory::new()
            ->withFieldcollection(
                'fieldcollection',
                itemWithLocalizedText(),
            )
            ->create(['input' => 'in the database']),
        fn (TestFieldTypes $version) => $version->getFieldcollection()->getItems()[0],
    ],
    'the localized fields of a field collection item' => [
        fn () => TestFieldTypesFactory::new()
            ->withFieldcollection(
                'fieldcollection',
                itemWithLocalizedText(),
            )
            ->create(['input' => 'in the database']),
        fn (TestFieldTypes $version) => $version->getFieldcollection()->getItems()[0]->getLocalizedFields(),
    ],
    'an object brick' => [
        fn () => TestLazyRelationsFactory::new()
            ->withObjectbrick(
                'bricks',
                TestLocalizedLazyBrick::class,
                ['lInput' => 'some text'],
            )
            ->create(['input' => 'in the database']),
        fn (TestLazyRelations $version) => $version->getBricks()->getItems()[0],
    ],
    'the localized fields of an object brick' => [
        fn () => TestLazyRelationsFactory::new()
            ->withObjectbrick(
                'bricks',
                TestLocalizedLazyBrick::class,
                ['lInput' => 'some text'],
            )
            ->create(['input' => 'in the database']),
        fn (TestLazyRelations $version) => $version->getBricks()->getItems()[0]->getLocalizedFields(),
    ],
]);
