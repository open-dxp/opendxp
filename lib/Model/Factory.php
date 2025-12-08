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

namespace OpenDxp\Model;

use OpenDxp\Loader\ImplementationLoader\ClassMapLoader;
use OpenDxp\Loader\ImplementationLoader\ImplementationLoader;
use Override;

/**
 * @internal
 */
final class Factory extends ImplementationLoader implements FactoryInterface
{
    public function getClassMap(): array
    {
        $map = [];
        foreach ($this->loaders as $loader) {
            if ($loader instanceof ClassMapLoader) {
                $map = [...$map, ...$loader->getClassMap()];
            }
        }

        return $map;
    }

    #[Override]
    public function build(string $name, array $params = []): AbstractModel
    {
        return parent::build($name, $params);
    }
}
