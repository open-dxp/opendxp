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


namespace OpenDxp\Test;

use OpenDxp\Model\DataObject\Fieldcollection\Definition;
use OpenDxp\Model\DataObject\ClassDefinition\Service;
use RuntimeException;

final class Fieldcollections
{
    public static function install(string $key, string $definition): Definition
    {
        $json = file_get_contents($definition);

        if ($json === false) {
            throw new RuntimeException(sprintf('There is no fieldcollection definition at %s.', $definition));
        }

        $collection = Definition::getByKey($key);

        if (!$collection instanceof Definition) {
            $collection = new Definition();
            $collection->setKey($key);
        }

        Service::importFieldCollectionFromJson($collection, $json, true);

        return Definition::getByKey($key)
            ?? throw new RuntimeException(sprintf('The fieldcollection %s was not installed.', $key));
    }
}
