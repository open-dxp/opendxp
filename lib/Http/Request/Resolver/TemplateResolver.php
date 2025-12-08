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

use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class TemplateResolver extends AbstractRequestResolver
{
    public function getTemplate(?Request $request = null): ?string
    {
        if (!$request instanceof \Symfony\Component\HttpFoundation\Request) {
            $request = $this->getCurrentRequest();
        }

        return $request->attributes->get(DynamicRouter::CONTENT_TEMPLATE);
    }

    public function setTemplate(Request $request, string $template): void
    {
        $request->attributes->set(DynamicRouter::CONTENT_TEMPLATE, $template);
    }
}
