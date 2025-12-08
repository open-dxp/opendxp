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

namespace OpenDxp\Bundle\SeoBundle\Event\Model;

use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use OpenDxp\Event\Traits\ArgumentsAwareTrait;
use Symfony\Contracts\EventDispatcher\Event;

class RedirectEvent extends Event
{
    use ArgumentsAwareTrait;

    /**
     * @param array $arguments additional parameters (e.g. "versionNote" for the version note)
     */
    public function __construct(protected Redirect $redirect, array $arguments = [])
    {
        $this->arguments = $arguments;
    }

    public function getRedirect(): Redirect
    {
        return $this->redirect;
    }

    public function setRedirect(Redirect $redirect): void
    {
        $this->redirect = $redirect;
    }
}
