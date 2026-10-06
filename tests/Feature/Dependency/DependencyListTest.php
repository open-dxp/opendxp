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

use OpenDxp\Model\Dependency;
use OpenDxp\Model\Element\Service;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Tests\Factory\TestObjectFactory;

it('lists the elements an element requires, without the ones that no longer exist', function () {
    $assets = AssetImageFactory::createMany(3);
    $page = referencing(DocumentPageFactory::createOne(), ...$assets);
    orphanedDependencies('targetid', 30, [
        'sourceid' => $page->getId(),
        'sourcetype' => 'document',
        'targettype' => 'asset',
    ]);

    $dependencies = Dependency::getBySourceId($page->getId(), 'document');

    expect(dependencyIds(Service::getRequiresDependenciesForFrontend($dependencies, 0, 25)['requires']))
        ->toEqualCanonicalizing(elementIds($assets))
        ->and($dependencies->getRequiresTotalCount())
        ->toBe(3);
});

it('lists the elements that require an element, without the ones that no longer exist', function () {
    $asset = AssetImageFactory::createOne();
    $pages = [
        referencing(DocumentPageFactory::createOne(), $asset),
        referencing(DocumentPageFactory::createOne(), $asset),
    ];
    orphanedDependencies('sourceid', 30, [
        'sourcetype' => 'document',
        'targetid' => $asset->getId(),
        'targettype' => 'asset',
    ]);

    $dependencies = Dependency::getBySourceId($asset->getId(), 'asset');

    expect(dependencyIds(Service::getRequiredByDependenciesForFrontend($dependencies, 0, 25)['requiredBy']))
        ->toEqualCanonicalizing(elementIds($pages))
        ->and($dependencies->getRequiredByTotalCount())
        ->toBe(2)
        ->and($dependencies->isRequired())
        ->toBeTrue();
});

it('counts an element as unused when only elements that no longer exist required it', function () {
    $asset = AssetImageFactory::createOne();
    orphanedDependencies('sourceid', 3, [
        'sourcetype' => 'object',
        'targetid' => $asset->getId(),
        'targettype' => 'asset',
    ]);

    expect(Dependency::getBySourceId($asset->getId(), 'asset')->isRequired())
        ->toBeFalse();
});

it('filters the elements that require an element by their path, whatever their kind', function () {
    $asset = AssetImageFactory::createOne();
    $page = referencing(DocumentPageFactory::createOne(), $asset);
    $object = referencing(TestObjectFactory::createOne(), $asset);
    $image = referencing(AssetImageFactory::createOne(), $asset);

    $found = Dependency::getBySourceId($asset->getId(), 'asset')->getFilterRequiredByPath(0, 25, '/');

    expect(array_map(static fn (array $row): string => $row['type'] . ':' . $row['id'], $found))
        ->toEqualCanonicalizing([
            'document:' . $page->getId(),
            'object:' . $object->getId(),
            'asset:' . $image->getId(),
        ]);
});
