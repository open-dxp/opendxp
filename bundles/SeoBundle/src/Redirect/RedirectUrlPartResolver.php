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

namespace OpenDxp\Bundle\SeoBundle\Redirect;

use InvalidArgumentException;
use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class RedirectUrlPartResolver
{
    private array $parts = [];

    /**
     * RedirectUrlPartResolver constructor.
     *
     */
    public function __construct(private readonly Request $request)
    {
    }

    public function getRequestUriPart(string $type): string
    {
        if (isset($this->parts[$type])) {
            return $this->parts[$type];
        }

        $part = null;
        switch ($type) {
            case Redirect::TYPE_ENTIRE_URI:
                $part = $this->request->getUri();

                break;

            case Redirect::TYPE_PATH_QUERY:
                $part = $this->request->getRequestUri();

                break;

            case Redirect::TYPE_AUTO_CREATE:
            case Redirect::TYPE_PATH:
                $part = $this->request->getPathInfo();

                break;
        }

        if (null === $part) {
            throw new InvalidArgumentException(sprintf('Unsupported request URI part type "%s"', $type));
        }

        $this->parts[$type] = urldecode($part);

        return $this->parts[$type];
    }
}
