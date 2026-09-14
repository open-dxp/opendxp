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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Tool;

use __PHP_Incomplete_Class;
use Closure;
use OpenDxp;
use OpenDxp\Config;
use SplObjectStorage;
use Throwable;

final class Serialize
{
    protected static array $loopFilterProcessedObjects = [];

    public static function serialize(mixed $data): string
    {
        return serialize($data);
    }

    public static function unserialize(?string $data = null, array $options = []): mixed
    {
        if ($data) {
            return unserialize($data, $options);
        }

        return $data;
    }

    /**
     * A disallowed class doesn't necessarily fail the whole unserialize() call, only the object
     * node it belongs to: the result can still come back non-null with a __PHP_Incomplete_Class
     * sitting a few properties deep inside it, which only surfaces once something calls a method
     * on that specific property. Rejecting the whole value here if any part of it is incomplete
     * turns that into a clean null right away, the same as if the entry didn't exist.
     */
    public static function unserializeWithScope(SerializationScope $scope, ?string $data): mixed
    {
        $allowedClasses = array_keys(Config::getSystemConfiguration()['serialization'][$scope->value]['allowed_classes'] ?? []);

        $value = self::unserialize($data, ['allowed_classes' => $allowedClasses]);

        return self::hasIncompleteClass($value) ? null : $value;
    }

    private static function hasIncompleteClass(mixed $value, ?SplObjectStorage $visitedObjects = null): bool
    {
        if ($value instanceof __PHP_Incomplete_Class) {
            return true;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if (self::hasIncompleteClass($item, $visitedObjects)) {
                    return true;
                }
            }

            return false;
        }

        if (!is_object($value)) {
            return false;
        }

        $visitedObjects ??= new SplObjectStorage();
        if ($visitedObjects->contains($value)) {
            return false;
        }

        $visitedObjects->attach($value);

        $properties = (Closure::bind(fn () => get_object_vars($this), $value, $value::class))();
        foreach ($properties as $property) {
            if (self::hasIncompleteClass($property, $visitedObjects)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @internal
     *
     * Shortcut to access the admin serializer
     */
    public static function getAdminSerializer(): \Symfony\Component\Serializer\Serializer
    {
        return OpenDxp::getContainer()->get('opendxp_admin.serializer');
    }

    /**
     * @internal
     *
     * this is a special json encoder that avoids recursion errors
     * especially for opendxp models that contain massive self referencing objects
     */
    public static function removeReferenceLoops(mixed $data): mixed
    {
        self::$loopFilterProcessedObjects = []; // reset

        return self::loopFilterCycles($data);
    }

    protected static function loopFilterCycles(mixed $element): mixed
    {
        if (is_array($element)) {
            foreach ($element as &$value) {
                $value = self::loopFilterCycles($value);
            }
        } elseif (is_object($element)) {
            try {
                $clone = clone $element; // do not modify the original object
            } catch (Throwable $e) {
                return sprintf('"* NON-CLONEABLE (%s): %s *"', $element::class, $e->getMessage());
            }

            if (in_array($element, self::$loopFilterProcessedObjects, true)) {
                return '"* RECURSION (' . $element::class . ') *"';
            }

            self::$loopFilterProcessedObjects[] = $element;

            $propCollection = get_object_vars($clone);

            foreach ($propCollection as $name => $propValue) {
                if (!str_starts_with($name, "\0")) {
                    $clone->$name = self::loopFilterCycles($propValue);
                }
            }

            array_splice(self::$loopFilterProcessedObjects, array_search($element, self::$loopFilterProcessedObjects, true), 1);

            return $clone;
        }

        return $element;
    }
}
