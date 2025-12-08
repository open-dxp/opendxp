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

use OpenDxp\Twig\Extension\Templating\Inc;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @internal
 */
class SubrequestExtension extends AbstractExtension
{
    protected Inc $incHelper;

    public function __construct(Inc $incHelper)
    {
        $this->incHelper = $incHelper;
    }

    #[Override]
    public function getFunctions(): array
    {
        // as runtime extension classes are invokable, we can pass them directly as callable
        return [
            new TwigFunction('opendxp_inc', $this->incHelper, [
                'is_safe' => ['html'],
            ]),
        ];
    }
}
