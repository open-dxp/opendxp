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

namespace OpenDxp\Model\DataObject\ClassDefinition\Layout;

use OpenDxp\Model;
use OpenDxp\Model\DataObject\ClassDefinition\Data\LayoutDefinitionEnrichmentInterface;
use OpenDxp\Model\DataObject\Concrete;

class Iframe extends Model\DataObject\ClassDefinition\Layout implements LayoutDefinitionEnrichmentInterface
{
    /**
     * Static type of this element
     *
     * @internal
     *
     */
    public string $fieldtype = 'iframe';

    /**
     * @internal
     *
     */
    public string $iframeUrl;

    /**
     * @internal
     *
     */
    public string $renderingData;

    public function getIframeUrl(): string
    {
        return $this->iframeUrl;
    }

    public function setIframeUrl(string $iframeUrl): void
    {
        $this->iframeUrl = $iframeUrl;
    }

    public function getRenderingData(): string
    {
        return $this->renderingData;
    }

    public function setRenderingData(string $renderingData): void
    {
        $this->renderingData = $renderingData;
    }

    public function enrichLayoutDefinition(?Concrete $object, array $context = []): static
    {
        $this->width = $this->getWidth() ?: 500;
        $this->height = $this->getHeight() ?: 500;

        return $this;
    }
}
