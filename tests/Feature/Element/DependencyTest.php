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


namespace OpenDxp\Tests\Feature\Element;

use OpenDxp\Db;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Property;
use OpenDxp\Tests\Factory\UnittestFactory;

function dependenciesOf(string $type, int $id): int
{
    return (int) Db::get()->fetchOne(
        'SELECT count(*) FROM dependencies WHERE sourceType = ? AND sourceId = ?',
        [$type, $id],
    );
}

function pointsAt(ElementInterface $source, array $targets): void
{
    $properties = [];

    foreach ($targets as $position => $target) {
        $property = new Property();
        $property->setType('object');
        $property->setName('prop_' . $position);
        $property->setCtype('object');
        $property->setDataFromEditmode($target);
        $properties['prop_' . $position] = $property;
    }

    $source->setProperties($properties);
    $source->save();
}

it('keeps one dependency per element a relation points at', function () {

    $source = UnittestFactory::createOne();
    $targets = UnittestFactory::createMany(5);

    expect(dependenciesOf('object', $source->getId()))->toBe(0);

    $source->setMultihref([$targets[0], $targets[1]]);
    $source->save();

    expect(dependenciesOf('object', $source->getId()))->toBe(2);

    $source->setMultihref([$targets[0], $targets[3], $targets[4]]);
    $source->save();

    expect(dependenciesOf('object', $source->getId()))->toBe(3);
});

it('names the element a relation no longer points at as no dependency', function () {

    $source = UnittestFactory::createOne();
    $targets = UnittestFactory::createMany(5);

    $source->setMultihref([$targets[0], $targets[1]]);
    $source->save();

    $source->setMultihref([$targets[0]]);
    $source->save();

    $left = Db::get()->fetchOne(
        'SELECT count(*) FROM dependencies WHERE sourceType = ? AND sourceId = ? AND targetId = ?',
        ['object', $source->getId(), $targets[1]->getId()],
    );

    expect((int) $left)->toBe(0);
});

it('names what it requires and what requires it', function (string $element, string $factory) {

    $source = $factory::createOne();
    $targets = UnittestFactory::createMany(3);

    pointsAt($source, $targets);

    $targets[2]->setMultihref([$source]);
    $targets[2]->save();

    $dependencies = $element::getById($source->getId(), ['force' => true])->getDependencies();

    expect(array_column($dependencies->getRequires(), 'id'))
        ->toEqualCanonicalizing(array_map(static fn (object $target) => $target->getId(), $targets))
        ->and(array_column($dependencies->getRequiredBy(), 'id'))
        ->toBe([$targets[2]->getId()]);
})->with('elements');
