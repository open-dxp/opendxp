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
use OpenDxp\Model\DataObject\ClassDefinition\Data as Definition;
use OpenDxp\Model\DataObject\Data\InputQuantityValue;
use OpenDxp\Model\DataObject\Data\QuantityValue;
use OpenDxp\Normalizer\NormalizerInterface;
use OpenDxp\Tests\Factory\TestObjectFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

// A relation normalizes to an id, so there have to be elements to point at.
beforeEach(fn () => TestObjectFactory::createMany(2));

it('reads a value back out of the form it normalized into', function (Closure $field, Closure $value) {

    $definition = $field();
    $original = $value();

    expect($definition)->toBeInstanceOf(NormalizerInterface::class);

    expect($definition->denormalize($definition->normalize($original), ownerInfo()))->toEqual($original);
})->with('normalizable values');

it('normalizes a date to its timestamp', function (Definition $definition) {

    $date = \Carbon\Carbon::createFromTimestamp(1700000000);

    expect($definition->normalize($date))->toBe(1700000000);
})->with([
    'a date' => [fn () => new Definition\Date()],
    'a date and a time' => [fn () => new Definition\Datetime()],
]);

it('reads a quantity back with its value and its unit', function (Definition $definition, object $original) {

    $back = $definition->denormalize($definition->normalize($original));

    expect($definition->normalize($original))
        ->not->toEqual($original)
        ->and($back)
        ->toBeInstanceOf($original::class)
        ->and($back->getValue())
        ->toEqual($original->getValue())
        ->and($back->getUnitId())
        ->toBe($original->getUnitId());
})->with([
    'a quantity' => [fn () => new Definition\QuantityValue(), fn () => new QuantityValue(123.4, aUnit('cm'))],
    'a quantity written as text' => [
        fn () => new Definition\InputQuantityValue(),
        fn () => new InputQuantityValue('123', aUnit('cm')),
    ],
]);

it('reads a relation back as the elements it pointed at', function (Definition $definition, array $elements) {

    $back = $definition->denormalize($definition->normalize($elements));

    expect($definition->normalize($elements))
        ->toHaveCount(count($elements))
        ->and(array_map(static fn ($element) => $element->getId(), $back))
        ->toBe(array_map(static fn ($element) => $element->getId(), $elements));
})->with([
    'objects' => [fn () => new Definition\ManyToManyObjectRelation(), fn () => someObjects(2)],
    'elements of any kind' => [
        fn () => new Definition\ManyToManyRelation(),
        fn () => [...someObjects(2), anImage('image.jpg')],
    ],
]);

it('reads a single relation back as the element it pointed at', function () {

    $definition = new Definition\ManyToOneRelation();
    $target = someObjects(1)[0];

    expect($definition->denormalize($definition->normalize($target))->getId())->toBe($target->getId());
});

it('reads the localized fields back with the values of every language', function () {

    $object = UnittestFactory::createOne();
    $target = TestObjectFactory::createOne();

    $object->setLinput('123');
    $object->setLobjects([$target]);

    $definition = $object->getClass()->getFieldDefinition('localizedfields');
    // A localized field reads the languages off the object it belongs to, so denormalizing needs it.
    $back = $definition->denormalize(
        $definition->normalize($object->getLocalizedfields()),
        ['object' => $object],
    );

    expect($back->getLocalizedValue('linput'))
        ->toBe('123')
        ->and($back->getLocalizedValue('lobjects')[0]->getId())
        ->toBe($target->getId());
});
