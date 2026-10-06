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
use OpenDxp\Model\DataObject\LazyLoading;
use OpenDxp\Model\DataObject\Objectbrick\Data\LazyLoadingLocalizedTest;
use OpenDxp\Model\DataObject\Unittest;
use OpenDxp\Tests\Factory\LazyLoadingFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

function itemWithLocalizedText(): Fieldcollection\Data\Unittestfieldcollection
{
    $item = new Fieldcollection\Data\Unittestfieldcollection();
    $item->setLinput('textEN', 'en');

    return $item;
}

it('binds the fields of a version to that version', function (Concrete $object, Closure $field) {
    $object->setInput('in the version');

    $object->saveVersion();
    $version = reloaded($object)->getLatestVersion()->loadData();

    expect($field($version)->getObject())->getInput()->toBe('in the version');
})->with([
    'the localized fields of an object' => [
        fn () => UnittestFactory::new()
            ->withLocalizedValues(
                'linput',
                ['en' => 'some text'],
            )
            ->create(['input' => 'in the database']),
        fn (Unittest $version) => $version->getLocalizedfields(),
    ],
    'an item of a field collection' => [
        fn () => UnittestFactory::new()
            ->withFieldcollection(
                'fieldcollection',
                itemWithLocalizedText(),
            )
            ->create(['input' => 'in the database']),
        fn (Unittest $version) => $version->getFieldcollection()->getItems()[0],
    ],
    'the localized fields of a field collection item' => [
        fn () => UnittestFactory::new()
            ->withFieldcollection(
                'fieldcollection',
                itemWithLocalizedText(),
            )
            ->create(['input' => 'in the database']),
        fn (Unittest $version) => $version->getFieldcollection()->getItems()[0]->getLocalizedFields(),
    ],
    'an object brick' => [
        fn () => LazyLoadingFactory::new()
            ->withObjectbrick(
                'bricks',
                LazyLoadingLocalizedTest::class,
                ['lInput' => 'some text'],
            )
            ->create(['input' => 'in the database']),
        fn (LazyLoading $version) => $version->getBricks()->getItems()[0],
    ],
    'the localized fields of an object brick' => [
        fn () => LazyLoadingFactory::new()
            ->withObjectbrick(
                'bricks',
                LazyLoadingLocalizedTest::class,
                ['lInput' => 'some text'],
            )
            ->create(['input' => 'in the database']),
        fn (LazyLoading $version) => $version->getBricks()->getItems()[0]->getLocalizedFields(),
    ],
]);
