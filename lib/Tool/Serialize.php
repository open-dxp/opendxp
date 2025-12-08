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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Tool;

use OpenDxp;
use Throwable;

final class Serialize
{
    protected static array $loopFilterProcessedObjects = [];

    public static function serialize(mixed $data): string
    {
        return serialize($data);
    }

    public static function unserialize(?string $data = null): mixed
    {
        if ($data) {
            return unserialize($data);
        }

        return $data;
    }

    /**
     * @internal
     *
     * Shortcut to access the admin serializer
     *
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
     *
     *
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
