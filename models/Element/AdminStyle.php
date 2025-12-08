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

namespace OpenDxp\Model\Element;

use OpenDxp;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\AbstractObject;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Folder;
use OpenDxp\Model\Document;
use OpenDxp\Model\Site;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdminStyle
{
    protected string|bool|null $elementCssClass = '';

    protected string|bool|null $elementIcon = null;

    protected string|bool|null $elementIconClass = null;

    protected ?array $elementQtipConfig = null;

    protected ?string $elementText = null;

    public function __construct(ElementInterface $element)
    {
        $this->setElementText($element->getKey());
        if ($element instanceof AbstractObject) {
            if ($element instanceof Folder) {
                $this->elementIconClass = 'opendxp_icon_folder';
                $this->elementQtipConfig = [
                    'title' => 'ID: ' . $element->getId(),
                ];
            } elseif ($element instanceof Concrete) {
                if ($element->getClass()->getIcon()) {
                    $this->elementIcon = $element->getClass()->getIcon();
                } else {
                    $this->elementIconClass = $element->getType() === 'variant' ? 'opendxp_icon_variant' : 'opendxp_icon_object';
                }

                $this->elementQtipConfig = [
                    'title' => 'ID: ' . $element->getId(),
                    'text' => 'Type: ' . $element->getClass()->getName(),
                ];
            }
        } elseif ($element instanceof Asset) {
            $this->elementQtipConfig = [
                'title' => 'ID: ' . $element->getId(),
            ];

            if ($element->getType() === 'folder') {
                $this->elementIconClass = 'opendxp_icon_folder';
            } else {
                $this->elementIconClass = 'opendxp_icon_asset_default';

                $fileExt = pathinfo($element->getFilename(), PATHINFO_EXTENSION);
                if ($fileExt) {
                    $this->elementIconClass .= ' opendxp_icon_' . strtolower(pathinfo($element->getFilename(), PATHINFO_EXTENSION));
                }
            }
        } elseif ($element instanceof Document) {
            $this->elementQtipConfig = [
                'title' => 'ID: ' . $element->getId(),
                'text' => 'Type: ' . $element->getType(),
            ];

            $this->elementIconClass = 'opendxp_icon_' . $element->getType();

            // set type specific settings
            if ($element->getType() === 'page') {
                $site = Site::getByRootId($element->getId());

                if ($site instanceof Site) {
                    $translator = OpenDxp::getContainer()->get(TranslatorInterface::class);
                    $this->elementQtipConfig['text'] .= '<br>' . $translator->trans('site_id', [], 'admin') . ': ' . $site->getId();
                }

                $this->elementIconClass = 'opendxp_icon_page';

                if ($element instanceof Document\Page && $element->getStaticGeneratorEnabled()) {
                    $this->elementIconClass = 'opendxp_icon_page_static';
                }

                // test for a site
                if ($site = Site::getByRootId($element->getId())) {
                    $this->elementIconClass = 'opendxp_icon_site';
                }
            } elseif (in_array($element->getType(), ['folder', 'link', 'hardlink'], true)) {
                if (!$element->hasChildren() && $element->getType() === 'folder') {
                    $this->elementIconClass = 'opendxp_icon_folder';
                }
            }
        }
    }

    public function setElementCssClass(bool|string|null $elementCssClass): static
    {
        $this->elementCssClass = $elementCssClass;

        return $this;
    }

    public function appendElementCssClass(string $elementCssClass): static
    {
        $this->elementCssClass .= ' ' . $elementCssClass;

        return $this;
    }

    public function getElementCssClass(): bool|string|null
    {
        return $this->elementCssClass;
    }

    public function setElementIcon(bool|string|null $elementIcon): static
    {
        $this->elementIcon = $elementIcon;

        return $this;
    }

    /**
     * @return string|bool|null Return false if you don't want to overwrite the default.
     */
    public function getElementIcon(): bool|string|null
    {
        return $this->elementIcon;
    }

    public function setElementIconClass(bool|string|null $elementIconClass): static
    {
        $this->elementIconClass = $elementIconClass;

        return $this;
    }

    /**
     * @return string|bool|null Return false if you don't want to overwrite the default.
     */
    public function getElementIconClass(): bool|string|null
    {
        return $this->elementIconClass;
    }

    public function getElementQtipConfig(): ?array
    {
        return $this->elementQtipConfig;
    }

    public function setElementQtipConfig(?array $elementQtipConfig): void
    {
        $this->elementQtipConfig = $elementQtipConfig;
    }

    public function getElementText(): ?string
    {
        return $this->elementText;
    }

    public function setElementText(?string $elementText): void
    {
        $this->elementText = $elementText;
    }
}
