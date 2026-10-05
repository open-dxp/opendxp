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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Test;

use OpenDxp;
use Override;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @deprecated since OpenDXP 1.5 and will be removed in 2.0
 */
abstract class WebTestCase extends \Symfony\Bundle\FrameworkBundle\Test\WebTestCase
{
    #[Override]
    protected static function createKernel(array $options = []): KernelInterface
    {
        trigger_deprecation('open-dxp/opendxp', '1.5', 'Extending "%s" is deprecated and will be removed in 2.0. Use "OpenDxp\TestFoundation\BrowserTestCase" instead.', self::class);

        $kernel = parent::createKernel($options);

        OpenDxp::setKernel($kernel);

        return $kernel;
    }
}
