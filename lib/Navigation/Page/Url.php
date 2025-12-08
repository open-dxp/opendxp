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

/**
 * ----------------------------------------------------------------------------------
 * based on @author ZF1 Zend_Navigation_Page_Uri
 * ----------------------------------------------------------------------------------
 */

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace OpenDxp\Navigation\Page;

use OpenDxp\Navigation\Page;
use Override;

class Url extends Page
{
    /**
     * Page URI
     */
    protected ?string $_uri = null;

    /**
     * Sets page URI
     *
     * @param string|null $uri page URI, must a string or null
     *
     * @return $this fluent interface, returns self
     */
    public function setUri(?string $uri): static
    {
        $this->_uri = $uri;

        return $this;
    }

    /**
     * Returns URI
     *
     */
    public function getUri(): ?string
    {
        return $this->_uri;
    }

    public function getHref(): string
    {
        $uri = $this->getUri();

        $fragment = $this->getFragment();
        if (null !== $fragment) {
            if (str_ends_with($uri, '#')) {
                return $uri . $fragment;
            }

            return $uri . '#' . $fragment;
        }

        return $uri ?? '';
    }

    // Public methods:

    #[Override]
    public function toArray(): array
    {
        return [...parent::toArray(), 'uri' => $this->getUri()];
    }
}
