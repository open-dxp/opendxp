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

namespace OpenDxp\Model\Document\Editable;

use DOMElement;
use OpenDxp;
use OpenDxp\Model;
use OpenDxp\Tool\DomCrawler;
use OpenDxp\Tool\Text;
use Override;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;

/**
 * @method \OpenDxp\Model\Document\Editable\Dao getDao()
 */
class Wysiwyg extends Model\Document\Editable implements IdRewriterInterface, EditmodeDataInterface
{
    private static HtmlSanitizer $openDxpWysiwygSanitizer;

    /**
     * Contains the text
     *
     * @internal
     */
    protected ?string $text = null;

    private function getWysiwygSanitizer(): HtmlSanitizer
    {
        return self::$openDxpWysiwygSanitizer ??= OpenDxp::getContainer()->get(Text::OPENDXP_WYSIWYG_SANITIZER_ID);
    }

    public function getType(): string
    {
        return 'wysiwyg';
    }

    public function getData(): mixed
    {
        return (string) $this->text;
    }

    public function getText(): string
    {
        return $this->getData();
    }

    public function getDataEditmode(): ?string
    {
        $document = $this->getDocument();

        return Text::wysiwygText($this->text, [
            'document' => $document,
            'context' => $this,
        ]);
    }

    public function frontend()
    {
        $document = $this->getDocument();

        return Text::wysiwygText($this->text, [
                'document' => $document,
                'context' => $this,
            ]);
    }

    public function setDataFromResource(mixed $data): static
    {
        $this->text = $data;

        return $this;
    }

    public function setDataFromEditmode(mixed $data): static
    {
        $this->text = $data;

        return $this;
    }

    public function isEmpty(): bool
    {
        return empty($this->text);
    }

    #[Override]
    public function resolveDependencies(): array
    {
        return Text::getDependenciesOfWysiwygText($this->text);
    }

    #[Override]
    public function getCacheTags(Model\Document\PageSnippet $ownerDocument, array $tags = []): array
    {
        return Text::getCacheTagsOfWysiwygText($this->text, $tags);
    }

    public function rewriteIds(array $idMapping): void
    {
        $html = new DomCrawler($this->text);

        $elements = $html->filter('a[opendxp_id], img[opendxp_id]');

        /** @var DOMElement $el */
        foreach ($elements as $el) {
            if ($el->hasAttribute('href') || $el->hasAttribute('src')) {
                $type = $el->getAttribute('opendxp_type');
                $id = (int)$el->getAttribute('opendxp_id');

                if ($idMapping[$type][$id] ?? false) {
                    $el->setAttribute('opendxp_id', strtr($el->getAttribute('opendxp_id'), $idMapping[$type]));
                }
            }
        }

        $this->text = $html->html();

        $html->clear();
        unset($html);
    }

    public function save(): void
    {
        if (is_string($this->text)) {
            $helper = $this->getWysiwygSanitizer();
            $this->text = $helper->sanitizeFor('body', $this->text);
        }
        $this->getDao()->save();
    }
}
