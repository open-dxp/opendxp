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

use Exception;
use Generator;
use OpenDxp\Model\Document\Editable\BlockInterface;
use OpenDxp\Model\Document\PageSnippet;
use OpenDxp\Templating\Renderer\EditableRenderer;
use OpenDxp\Twig\TokenParser\BlockParser;
use OpenDxp\Twig\TokenParser\ManualBlockParser;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @internal
 */
class DocumentEditableExtension extends AbstractExtension
{
    public function __construct(protected EditableRenderer $editableRenderer)
    {
    }

    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('opendxp_*', $this->renderEditable(...), [
                'needs_context' => true,
                'is_safe' => ['html'],
            ]),
            new TwigFunction('opendxp_iterate_block', $this->getBlockIterator(...)),
        ];

        // @phpstan-ignore-next-line those are just for auto-complete, not nice, but works ;-)
        new TwigFunction('opendxp_area');
        new TwigFunction('opendxp_areablock');
        new TwigFunction('opendxp_block');
        new TwigFunction('opendxp_checkbox');
        new TwigFunction('opendxp_date');
        new TwigFunction('opendxp_embed');
        new TwigFunction('opendxp_image');
        new TwigFunction('opendxp_input');
        new TwigFunction('opendxp_link');
        new TwigFunction('opendxp_multiselect');
        new TwigFunction('opendxp_numeric');
        new TwigFunction('opendxp_pdf');
        new TwigFunction('opendxp_relation');
        new TwigFunction('opendxp_relations');
        new TwigFunction('opendxp_renderlet');
        new TwigFunction('opendxp_scheduledblock');
        new TwigFunction('opendxp_select');
        new TwigFunction('opendxp_snippet');
        new TwigFunction('opendxp_table');
        new TwigFunction('opendxp_textarea');
        new TwigFunction('opendxp_video');
        new TwigFunction('opendxp_wysiwyg');
    }

    /**
     * @internal
     *
     * @throws Exception
     */
    public function renderEditable(array $context, string $type, string $name, array $options = []): string|\OpenDxp\Model\Document\Editable\EditableInterface
    {
        $document = $context['document'] ?? null;
        if (!($document instanceof PageSnippet)) {
            return '';
        }
        $editmode = $context['editmode'] ?? false;

        return $this->editableRenderer->render($document, $type, $name, $options, $editmode);
    }

    /**
     * Returns an iterator which can be used instead of while($block->loop())
     *
     * @internal
     *
     *
     */
    public function getBlockIterator(BlockInterface $block): Generator
    {
        return $block->getIterator();
    }

    #[Override]
    public function getTokenParsers(): array
    {
        return [
            new BlockParser(),
            new ManualBlockParser(),
        ];
    }
}
