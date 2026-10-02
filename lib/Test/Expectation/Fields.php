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
        expect()->extend('toCarryField', function (string $field, mixed $expected, ?string $language = null): Expectation {
            /** @var Concrete $object */
            $object = $this->value;

            $definition = Fields::definitionOf($object, $field);

            $getter = 'get' . ucfirst($field);
            $carried = $language === null ? $object->{$getter}() : $object->{$getter}($language);

            expect($definition->isEqual($expected, $carried))
                ->toBeTrue(sprintf('%s carries what it was given%s', $field, $language === null ? '' : ' in ' . $language));

            return $this;
        });
    }

    /**
     * The definition of a field, whether the class holds it directly or inside its localized fields.
     * Public because Pest binds the closure above to the expectation, which reaches no private method
     * of this class.
     */
    public static function definitionOf(Concrete $object, string $field): EqualComparisonInterface
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
