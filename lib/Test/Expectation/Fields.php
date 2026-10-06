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

namespace OpenDxp\Test\Expectation;

use OpenDxp\Model\DataObject\ClassDefinition\Data\EqualComparisonInterface;
use OpenDxp\Model\DataObject\ClassDefinition\Data\Localizedfields;
use OpenDxp\Model\DataObject\Concrete;
use Pest\Expectation;
use RuntimeException;

/**
 * Compares a field of an object with the value it was given. Every field type brings its own idea of
 * equality, so the comparison asks the field definition instead of comparing the values directly.
 */
final class Fields
{
    public static function register(): void
    {
        $definitionOf = self::definitionOf(...);

        expect()->extend(
            'toCarryField',
            function (string $field, mixed $expected) use ($definitionOf): Expectation {
                /** @var Concrete $object */
                $object = $this->value;

                $definition = $definitionOf($object, $field);

                $carriesIt = $definition->isEqual(
                    $expected,
                    $object->get($field),
                );
                $message = sprintf('%s carries what it was given', $field);

                expect($carriesIt)->toBeTrue($message);

                return $this;
            },
        );

        expect()->extend(
            'toCarryLocalizedField',
            function (string $field, mixed $expected, string $language) use ($definitionOf): Expectation {
                /** @var Concrete $object */
                $object = $this->value;

                $definition = $definitionOf($object, $field);

                $carriesIt = $definition->isEqual(
                    $expected,
                    $object->get($field, $language),
                );
                $message = sprintf('%s carries what it was given in %s', $field, $language);

                expect($carriesIt)->toBeTrue($message);

                return $this;
            },
        );
    }

    private static function definitionOf(Concrete $object, string $field): EqualComparisonInterface
    {
        $definition = $object->getClass()->getFieldDefinition($field);

        if ($definition === null) {
            $localized = $object->getClass()->getFieldDefinition('localizedfields');

            $definition = $localized instanceof Localizedfields
                ? $localized->getFieldDefinition($field)
                : null;
        }

        if (!$definition instanceof EqualComparisonInterface) {
            throw new RuntimeException(sprintf('The definition of %s says nothing about equality.', $field));
        }

        return $definition;
    }
}
