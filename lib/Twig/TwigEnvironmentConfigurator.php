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

namespace OpenDxp\Twig;

use OpenDxp\Model\Document\Editable;
use Symfony\Bundle\TwigBundle\DependencyInjection\Configurator\EnvironmentConfigurator;
use Twig\Environment;
use Twig\Runtime\EscaperRuntime;

/**
 * @internal
 */
final readonly class TwigEnvironmentConfigurator
{
    public function __construct(
        private EnvironmentConfigurator $decorated,
    ) {
    }

    public function configure(Environment $environment): void
    {
        $this->decorated->configure($environment);

        $environment->getRuntime(EscaperRuntime::class)->addSafeClass(Editable::class, ['html']);
    }
}
