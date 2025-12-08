<?php

declare(strict_types = 1);

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

namespace OpenDxp\Loader\ImplementationLoader;

use OpenDxp\Loader\ImplementationLoader\Exception\UnsupportedException;

/**
 * @internal
 */
abstract class AbstractClassNameLoader implements LoaderInterface, ClassNameLoaderInterface
{
    abstract protected function getClassName(string $name): string;

    public function build(string $name, array $params = []): mixed
    {
        if (!$this->supports($name)) {
            throw new UnsupportedException(sprintf('"%s" is not supported', $name));
        }

        $params = array_values($params);

        $className = $this->getClassName($name);

        return new $className(...$params);
    }

    public function supportsClassName(string $name): bool
    {
        return $this->supports($name);
    }

    public function getClassNameFor(string $name): string
    {
        if (!$this->supports($name)) {
            throw new UnsupportedException(sprintf('"%s" is not supported', $name));
        }

        return $this->getClassName($name);
    }
}
