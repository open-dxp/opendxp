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

namespace OpenDxp\Navigation\Iterator;

use Exception;
use OpenDxp\Navigation\Container;
use OpenDxp\Navigation\Page;
use RecursiveFilterIterator;
use RecursiveIterator;

/**
 * @internal
 */
final class PrefixRecursiveFilterIterator extends RecursiveFilterIterator
{
    /**
     * @param RecursiveIterator $iterator navigation container to iterate
     * @param string $property name of property that acts as needle
     * @param string $value value which acts as haystack
     */
    public function __construct(RecursiveIterator $iterator, private readonly string $property, private readonly string $value)
    {
        parent::__construct($iterator);
    }

    public function accept(): bool
    {
        /** @var Page $page */
        $page = $this->current();

        try {
            $property = $page->get($this->property);
        } catch (Exception) {
            return false;
        }

        return is_string($property) && str_starts_with($this->value, $property);
    }

    public function getChildren(): ?RecursiveFilterIterator
    {
        /** @var Container $container */
        $container = $this->getInnerIterator();

        if ($container->getChildren() === null) {
            return null;
        }

        return new self($container->getChildren(), $this->property, $this->value);
    }
}
