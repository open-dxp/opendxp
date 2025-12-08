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

use OpenDxp\Twig\Extension\Templating\HeadLink;
use OpenDxp\Twig\Extension\Templating\HeadMeta;
use OpenDxp\Twig\Extension\Templating\HeadScript;
use OpenDxp\Twig\Extension\Templating\HeadStyle;
use OpenDxp\Twig\Extension\Templating\HeadTitle;
use OpenDxp\Twig\Extension\Templating\InlineScript;
use OpenDxp\Twig\Extension\Templating\Placeholder;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @internal
 */
class HeaderExtension extends AbstractExtension
{
    private readonly HeadLink $headLink;

    private readonly HeadMeta $headMeta;

    private readonly HeadScript $headScript;

    private readonly HeadStyle $headStyle;

    private readonly HeadTitle $headTitle;

    private readonly InlineScript $inlineScript;

    private readonly Placeholder $placeholder;

    public function __construct(HeadLink $headLink, HeadMeta $headMeta, HeadScript $headScript, HeadStyle $headStyle, HeadTitle $headTitle, InlineScript $inlineScript, Placeholder $placeholder)
    {
        $this->headLink = $headLink;
        $this->headMeta = $headMeta;
        $this->headScript = $headScript;
        $this->headStyle = $headStyle;
        $this->headTitle = $headTitle;
        $this->inlineScript = $inlineScript;
        $this->placeholder = $placeholder;
    }

    #[Override]
    public function getFunctions(): array
    {
        $options = [
            'is_safe' => ['html'],
        ];

        // as runtime extension classes are invokable, we can pass them directly as callable
        return [
            new TwigFunction('opendxp_head_link', $this->headLink, $options),
            new TwigFunction('opendxp_head_meta', $this->headMeta, $options),
            new TwigFunction('opendxp_head_script', $this->headScript, $options),
            new TwigFunction('opendxp_head_style', $this->headStyle, $options),
            new TwigFunction('opendxp_head_title', $this->headTitle, $options),
            new TwigFunction('opendxp_inline_script', $this->inlineScript, $options),
            new TwigFunction('opendxp_placeholder', $this->placeholder, $options),
        ];
    }
}
