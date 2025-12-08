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

namespace OpenDxp\Event\BundleManager;

use Symfony\Contracts\EventDispatcher\Event;

class PathsEvent extends Event
{
    /**
     * @var string[]
     */
    protected array $paths = [];

    /**
     * @param string[] $paths
     */
    public function __construct(array $paths = [])
    {
        $this->setPaths($paths);
    }

    /**
     * @return string[]
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * @param string[] $paths
     */
    public function setPaths(array $paths): void
    {
        $this->paths = [];
        $this->addPaths($paths);
    }

    /**
     * @param string[] $paths
     */
    public function addPaths(array $paths): void
    {
        $this->paths = [...$this->paths, ...$paths];
        $this->paths = array_unique($this->paths);
    }
}
