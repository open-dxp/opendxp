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

namespace OpenDxp\Twig\Extension;

use OpenDxp\Twig\Extension\Templating\Navigation;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @internal
 */
class NavigationExtension extends AbstractExtension
{
    public function __construct(private readonly Navigation $navigationExtension)
    {
    }

    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('opendxp_build_nav', $this->navigationExtension->build(...)),
            new TwigFunction('opendxp_render_nav', $this->navigationExtension->render(...), [
                'is_safe' => ['html'],
            ]),
            new TwigFunction('opendxp_nav_renderer', $this->navigationExtension->getRenderer(...)),
        ];
    }
}
