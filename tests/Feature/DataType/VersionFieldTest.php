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

/**
 * Writes an unpublished version that differs from what the database holds, and hands that version
 * back. Everything inside it has to point at the version, not at the object in the database.
 */
function theVersionBeside(Concrete $object): Concrete
{
    $written = $object::getById($object->getId(), ['force' => true]);
    $written->setInput((string) ((int) $written->getInput() + 1));
    $written->saveVersion();

    $fromDatabase = $object::getById($object->getId(), ['force' => true]);
    $fromVersion = $fromDatabase->getLatestVersion()->loadData();

    expect($fromVersion->getInput())->not->toBe($fromDatabase->getInput());

    return $fromVersion;
}

function withALocalizedField(Unittest $object): void
{
    $object->setLinput('some localized input');
}

function withAFieldCollection(Unittest $object): void
{
    $item = new Fieldcollection\Data\Unittestfieldcollection();
    $item->setLinput('textEN', 'en');
    $object->setFieldcollection(new Fieldcollection([$item], 'fieldcollection'));
}

function withABrick(LazyLoading $object): void
{
    $brick = new LazyLoadingLocalizedTest($object);
    $brick->setLInput('some text');
    $object->getBricks()->setLazyLoadingLocalizedTest($brick);
}

it('points a field of a version at that version and not at what the database holds', function (Closure $build, Closure $fill, Closure $read) {

    $object = $build();
    $fill($object);
    $object->save();

    $version = theVersionBeside($object);

    expect($read($version)->getObject()->getInput())->toBe($version->getInput());
})->with([
    'the localized fields of an object' => [
        static fn () => UnittestFactory::createOne(['input' => '1']),
        withALocalizedField(...),
        static fn (Unittest $version) => $version->getLocalizedfields(),
    ],
    'an item of a field collection' => [
        static fn () => UnittestFactory::createOne(['input' => '1']),
        withAFieldCollection(...),
        static fn (Unittest $version) => $version->getFieldcollection()->getItems()[0],
    ],
    'the localized fields of a field collection item' => [
        static fn () => UnittestFactory::createOne(['input' => '1']),
        withAFieldCollection(...),
        static fn (Unittest $version) => $version->getFieldcollection()->getItems()[0]->getLocalizedFields(),
    ],
    'an object brick' => [
        static fn () => LazyLoadingFactory::createOne(['input' => '1']),
        withABrick(...),
        static fn (LazyLoading $version) => $version->getBricks()->getItems()[0],
    ],
    'the localized fields of an object brick' => [
        static fn () => LazyLoadingFactory::createOne(['input' => '1']),
        withABrick(...),
        static fn (LazyLoading $version) => $version->getBricks()->getItems()[0]->getLocalizedFields(),
    ],
]);
