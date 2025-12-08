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

use LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
abstract class AbstractRequestResolver
{
    public function __construct(protected RequestStack $requestStack)
    {
    }

    protected function getCurrentRequest(): Request
    {
        if (!$this->requestStack->getCurrentRequest()) {
            throw new LogicException('A request must be available.');
        }

        return $this->requestStack->getCurrentRequest();
    }

    protected function getMainRequest(): Request
    {
        if (!$this->requestStack->getMainRequest()) {
            throw new LogicException('A main request must be available.');
        }

        return $this->requestStack->getMainRequest();
    }
}
