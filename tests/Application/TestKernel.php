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

namespace OpenDxp\Tests\Application;

use FOS\HttpCacheBundle\FOSHttpCacheBundle;
use OpenDxp\Bundle\ApplicationLoggerBundle\OpenDxpApplicationLoggerBundle;
use OpenDxp\Bundle\CustomReportsBundle\OpenDxpCustomReportsBundle;
use OpenDxp\Bundle\GlossaryBundle\OpenDxpGlossaryBundle;
use OpenDxp\Bundle\SeoBundle\OpenDxpSeoBundle;
use OpenDxp\Bundle\SimpleBackendSearchBundle\OpenDxpSimpleBackendSearchBundle;
use OpenDxp\Bundle\StaticRoutesBundle\OpenDxpStaticRoutesBundle;
use OpenDxp\HttpKernel\BundleCollection\BundleCollection;
use OpenDxp\TestFoundation\Kernel\TestKernel as Foundation;
use Override;

final class TestKernel extends Foundation
{
    #[Override]
    protected function registerCoreBundlesToCollection(BundleCollection $collection): void
    {
        parent::registerCoreBundlesToCollection($collection);

        $collection->addBundle(new OpenDxpApplicationLoggerBundle());
        $collection->addBundle(new OpenDxpCustomReportsBundle());
        $collection->addBundle(new OpenDxpGlossaryBundle());
        $collection->addBundle(new OpenDxpSeoBundle());
        $collection->addBundle(new OpenDxpSimpleBackendSearchBundle());
        $collection->addBundle(new OpenDxpStaticRoutesBundle());

        if ($this->getEnvironment() === 'http_cache') {
            $collection->addBundle(new FOSHttpCacheBundle());
        }
    }
}
