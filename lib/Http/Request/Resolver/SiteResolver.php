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

namespace OpenDxp\Http\Request\Resolver;

use OpenDxp\Model\Site;
use Symfony\Component\HttpFoundation\Request;

class SiteResolver extends AbstractRequestResolver
{
    const ATTRIBUTE_SITE = '_site';

    const ATTRIBUTE_SITE_PATH = '_site_path';

    public function setSite(Request $request, Site $site): void
    {
        $request->attributes->set(static::ATTRIBUTE_SITE, $site);
    }

    public function getSite(?Request $request = null): ?Site
    {
        if (!$request instanceof \Symfony\Component\HttpFoundation\Request) {
            $request = $this->getCurrentRequest();
        }

        return $request->attributes->get(static::ATTRIBUTE_SITE);
    }

    public function setSitePath(Request $request, string $path): void
    {
        $request->attributes->set(static::ATTRIBUTE_SITE_PATH, $path);
    }

    public function getSitePath(?Request $request = null): ?string
    {
        if (!$request instanceof \Symfony\Component\HttpFoundation\Request) {
            $request = $this->getCurrentRequest();
        }

        return $request->attributes->get(static::ATTRIBUTE_SITE_PATH);
    }

    public function isSiteRequest(?Request $request = null): bool
    {
        $site = $this->getSite($request);

        return $site instanceof Site;
    }
}
