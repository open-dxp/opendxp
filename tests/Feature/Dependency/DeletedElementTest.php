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

namespace OpenDxp\Tests\Feature\Dependency;

use OpenDxp\Test\Factory\AssetFolderFactory;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\DataObjectFolderFactory;
use OpenDxp\Test\Factory\DocumentFolderFactory;
use OpenDxp\Test\Factory\DocumentHardlinkFactory;
use OpenDxp\Test\Factory\DocumentLinkFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Tests\Factory\TestObjectFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

dataset('sources', [
    'a page' => [fn () => DocumentPageFactory::createOne()],
    'a document link' => [fn () => DocumentLinkFactory::createOne()],
    'a hardlink' => [fn () => DocumentHardlinkFactory::new()
        ->withSource(DocumentPageFactory::createOne())
        ->create()],
    'a document folder' => [fn () => DocumentFolderFactory::createOne()],
    'an image' => [fn () => AssetImageFactory::createOne()],
    'an asset folder' => [fn () => AssetFolderFactory::createOne()],
    'an object' => [fn () => TestObjectFactory::createOne()],
    'an object folder' => [fn () => DataObjectFolderFactory::createOne()],
]);

it('records the dependency of each kind of element', function ($source) {
    $target = AssetImageFactory::createOne();
    referencing($source, $target);

    expect(dependenciesOn($target))
        ->toBe(1);
})->with('sources');

it('forgets the dependencies on an element right after the element is deleted', function ($source) {
    $target = AssetImageFactory::createOne();
    referencing($source, $target);

    $target->delete();

    expect(dependenciesOn($target))
        ->toBe(0);
})->with('sources');

it('forgets the dependencies on an element once a worker ran the sanity checks', function ($source) {
    $target = AssetImageFactory::createOne();
    referencing($source, $target);

    $target->delete();
    runSanityChecks();

    expect(dependenciesOn($target))
        ->toBe(0);
})->with('sources');

it('forgets the dependencies on an element even when the element that points to it can no longer be saved', function () {
    $target = AssetImageFactory::createOne();
    $object = UnittestFactory::createOne([
        'published' => true,
    ]);

    // An import may save a published object without its mandatory fields, and the sanity check then fails to save it.
    $object->setMandatoryInputWithDefault(null);
    $object->setOmitMandatoryCheck(true);
    referencing($object, $target);

    $target->delete();
    runSanityChecks();

    expect(dependenciesOn($target))
        ->toBe(0);
});
