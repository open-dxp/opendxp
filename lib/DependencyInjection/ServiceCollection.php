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

namespace OpenDxp\DependencyInjection;

use IteratorAggregate;
use Psr\Container\ContainerInterface;
use Traversable;

/**
 * @internal
 */
class ServiceCollection implements IteratorAggregate
{
    public function __construct(private readonly ContainerInterface $container, private readonly array $ids)
    {
    }

    public function getIterator(): Traversable
    {
        foreach ($this->ids as $id) {
            yield $this->container->get($id);
        }
    }
}
