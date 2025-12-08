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

namespace OpenDxp\Routing\Loader;

use OpenDxp\Config\BundleConfigLocator;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;

/**
 * @internal
 */
class BundleRoutingLoader extends Loader
{
    public function __construct(private readonly BundleConfigLocator $locator)
    {
    }

    public function load(mixed $resource, ?string $type = null): mixed
    {
        $collection = new RouteCollection();
        $files = $this->locator->locate('routing');

        if ($files === []) {
            return $collection;
        }

        foreach ($files as $file) {
            $routes = $this->import($file);
            $collection->addCollection($routes);
        }

        return $collection;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return 'opendxp_bundle' === $type;
    }
}
