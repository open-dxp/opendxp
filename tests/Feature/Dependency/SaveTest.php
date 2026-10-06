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

use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

/**
 * @return list<int>
 */
function requiredIdsOf(ElementInterface $element): array
{
    return dependencyIds(reloaded($element)->getDependencies()->getRequires());
}

/**
 * @return list<int>
 */
function requiringIdsOf(ElementInterface $element): array
{
    return dependencyIds(reloaded($element)->getDependencies()->getRequiredBy());
}

it('records a dependency for every related element', function () {
    $targets = UnittestFactory::createMany(2);

    $source = UnittestFactory::createOne(['multihref' => $targets]);

    expect(requiredIdsOf($source))->toEqualCanonicalizing(elementIds($targets));
});

it('drops the dependency of a removed relation', function () {
    $kept = UnittestFactory::createOne();
    $removed = UnittestFactory::createOne();
    $source = UnittestFactory::createOne([
        'multihref' => [
            $kept,
            $removed,
        ],
    ]);

    $source->setMultihref([$kept]);
    $source->save();

    expect(requiredIdsOf($source))->toBe([$kept->getId()]);
});

it('lists what it requires and what requires it', function (ElementInterface $source) {
    $targets = AssetImageFactory::createMany(3);
    referencing($source, ...$targets);

    $requiring = UnittestFactory::createOne(['multihref' => [$source]]);

    expect(requiredIdsOf($source))
        ->toEqualCanonicalizing(elementIds($targets))
        ->and(requiringIdsOf($source))
        ->toBe([$requiring->getId()]);
})->with([
    'an asset' => [fn () => AssetImageFactory::createOne()],
    'a document' => [fn () => DocumentPageFactory::createOne()],
    'an object' => [fn () => UnittestFactory::createOne()],
]);

it('records the dependency of each kind of element', function (ElementInterface $source) {
    $target = AssetImageFactory::createOne();

    referencing($source, $target);

    expect(requiredIdsOf($source))->toContain($target->getId());
})->with('sources');
