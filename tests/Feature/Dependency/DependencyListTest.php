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

use OpenDxp\Db;
use OpenDxp\Model\Dependency;
use OpenDxp\Model\Element\Service;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Tests\Factory\TestObjectFactory;

/**
 * Writes dependencies on elements that do not exist.
 *
 * @param array<string, int|string> $row
 */
function writeOrphanedDependencies(string $column, int $count, array $row): void
{
    for ($i = 1; $i <= $count; $i++) {
        Db::get()->insert('dependencies', [
            ...$row,
            $column => 900000000 + $i,
        ]);
    }
}

it('lists only required elements that exist', function () {
    $assets = AssetImageFactory::createMany(3);
    $page = referencing(DocumentPageFactory::createOne(), ...$assets);
    writeOrphanedDependencies('targetid', 30, [
        'sourceid' => $page->getId(),
        'sourcetype' => 'document',
        'targettype' => 'asset',
    ]);

    $dependencies = Dependency::getBySourceId($page->getId(), 'document');
    $listed = Service::getRequiresDependenciesForFrontend($dependencies, 0, 25)['requires'];

    expect(dependencyIds($listed))
        ->toEqualCanonicalizing(elementIds($assets))
        ->and($dependencies->getRequiresTotalCount())
        ->toBe(3);
});

it('lists only requiring elements that exist', function () {
    $asset = AssetImageFactory::createOne();
    $pages = [
        referencing(DocumentPageFactory::createOne(), $asset),
        referencing(DocumentPageFactory::createOne(), $asset),
    ];
    writeOrphanedDependencies('sourceid', 30, [
        'sourcetype' => 'document',
        'targetid' => $asset->getId(),
        'targettype' => 'asset',
    ]);

    $dependencies = Dependency::getBySourceId($asset->getId(), 'asset');
    $listed = Service::getRequiredByDependenciesForFrontend($dependencies, 0, 25)['requiredBy'];

    expect(dependencyIds($listed))
        ->toEqualCanonicalizing(elementIds($pages))
        ->and($dependencies->getRequiredByTotalCount())
        ->toBe(2)
        ->and($dependencies->isRequired())
        ->toBeTrue();
});

it('counts an element as unused when only elements that no longer exist required it', function () {
    $asset = AssetImageFactory::createOne();
    writeOrphanedDependencies('sourceid', 3, [
        'sourcetype' => 'object',
        'targetid' => $asset->getId(),
        'targettype' => 'asset',
    ]);

    $dependencies = Dependency::getBySourceId($asset->getId(), 'asset');

    expect($dependencies->isRequired())->toBeFalse();
});

it('filters the requiring elements of every kind by their path', function () {
    $asset = AssetImageFactory::createOne();
    $page = referencing(DocumentPageFactory::createOne(['key' => 'matching-page']), $asset);
    $object = referencing(TestObjectFactory::createOne(['key' => 'matching-object']), $asset);
    $image = referencing(AssetImageFactory::createOne(['filename' => 'matching-image.jpg']), $asset);
    referencing(DocumentPageFactory::createOne(['key' => 'other-page']), $asset);

    $found = Dependency::getBySourceId($asset->getId(), 'asset')->getFilterRequiredByPath(0, 25, 'matching');

    expect($found)->toEqualCanonicalizing([
        [
            'id' => $page->getId(),
            'type' => 'document',
        ],
        [
            'id' => $object->getId(),
            'type' => 'object',
        ],
        [
            'id' => $image->getId(),
            'type' => 'asset',
        ],
    ]);
});
