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

namespace OpenDxp\Bundle\GlossaryBundle\Twig\Extension;

use OpenDxp\Bundle\GlossaryBundle\Tool\Processor;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * @internal
 */
class GlossaryExtension
{
    public function __construct(private readonly Processor $glossaryProcessor)
    {
    }

    #[\Twig\Attribute\AsTwigFilter('opendxp_glossary', isSafe: ['html'])]
    public function applyGlossary(string $string, array $options = []): string
    {
        if (!$string) {
            return $string;
        }

        return $this->glossaryProcessor->process($string, $options);
    }
}
