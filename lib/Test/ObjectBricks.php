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

use OpenDxp\Model\DataObject\ClassDefinition\Service;
use OpenDxp\Model\DataObject\Objectbrick\Definition;
use RuntimeException;

final class ObjectBricks
{
    public static function install(string $key, string $definition): Definition
    {
        $json = file_get_contents($definition);

        if ($json === false) {
            throw new RuntimeException(sprintf('There is no objectbrick definition at %s.', $definition));
        }

        $brick = Definition::getByKey($key);

        if (!$brick instanceof Definition) {
            $brick = new Definition();
            $brick->setKey($key);
        }

        Service::importObjectBrickFromJson($brick, $json, true);

        return Definition::getByKey($key)
            ?? throw new RuntimeException(sprintf('The objectbrick %s was not installed.', $key));
    }
}
