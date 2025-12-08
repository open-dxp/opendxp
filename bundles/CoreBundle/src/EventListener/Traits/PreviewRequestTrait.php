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

namespace OpenDxp\Bundle\CoreBundle\EventListener\Traits;

use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
trait PreviewRequestTrait
{
    protected function isPreviewRequest(Request $request): bool
    {
        if ($request->server->get('HTTP_X_PURPOSE') === 'preview') {
            return true;
        }

        if ($request->server->get('HTTP_PURPOSE') === 'preview') {
            return true;
        }

        return $request->query->getBoolean('opendxp_preview');
    }
}
