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
use OpenDxp\Model\DataObject\Concrete;
use Pest\Expectation;

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

            $definition = $object->getClass()->getFieldDefinition($field)
                ?? $object->getClass()->getFieldDefinition('localizedfields')->getFieldDefinition($field);

            expect($definition)->toBeInstanceOf(
                EqualComparisonInterface::class,
                sprintf('the definition of %s says nothing about equality', $field),
            );

            $getter = 'get' . ucfirst($field);
            $carried = $language === null ? $object->{$getter}() : $object->{$getter}($language);

            expect($definition->isEqual($expected, $carried))
                ->toBeTrue(sprintf('%s carries what it was given%s', $field, $language === null ? '' : ' in ' . $language));

            return $this;
        });
    }
}
