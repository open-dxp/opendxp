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
use Override;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Traversable;

/**
 * Service locator exposing all of its services as collection
 *
 * @internal
 */
class CollectionServiceLocator extends ServiceLocator implements IteratorAggregate
{
    private readonly array $ids;

    public function __construct($factories)
    {
        $this->ids = array_keys($factories);

        parent::__construct($factories);
    }

    public function all(): array
    {
        return array_map($this->get(...), $this->ids);
    }

    #[Override]
    public function getIterator(): Traversable
    {
        foreach ($this->ids as $id) {
            yield $this->get($id); // @phpstan-ignore-line
        }
    }
}
