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

use League\Flysystem\FilesystemOperator;
use OpenDxp;
use Psr\Container\ContainerInterface;

/**
 * @internal
 */
class Storage
{
    public function __construct(private readonly ContainerInterface $locator)
    {
    }

    public function getStorage(string $name): FilesystemOperator
    {
        return $this->locator->get(sprintf('opendxp.%s.storage', $name));
    }

    public static function get(string $name): FilesystemOperator
    {
        $storage = OpenDxp::getContainer()->get(self::class);

        return $storage->getStorage($name);
    }
}
