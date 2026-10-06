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

namespace OpenDxp\Model\DataObject\ClassDefinition\Layout;

use OpenDxp;
use OpenDxp\Logger;
use OpenDxp\Model;
use OpenDxp\Model\DataObject\Concrete;
use Twig\Sandbox\Sandbox;
use Twig\Sandbox\SecurityError;

class Text extends Model\DataObject\ClassDefinition\Layout implements Model\DataObject\ClassDefinition\Data\LayoutDefinitionEnrichmentInterface
{
    /**
     * Static type of this element
     *
     * @internal
     */
    public string $fieldtype = 'text';

    /**
     * @internal
     */
    public string $html = '';

    /**
     * @internal
     */
    public string $renderingClass = '';

    /**
     * @internal
     */
    public string $renderingData;

    /**
     * @internal
     */
    public bool $border = false;

    public function getHtml(): string
    {
        return $this->html;
    }

    /**
     * @return $this
     */
    public function setHtml(string $html): static
    {
        $this->html = $html;

        return $this;
    }

    public function getRenderingClass(): string
    {
        return $this->renderingClass;
    }

    public function setRenderingClass(string $renderingClass): void
    {
        $this->renderingClass = $renderingClass;
    }

    public function getRenderingData(): string
    {
        return $this->renderingData;
    }

    public function setRenderingData(string $renderingData): void
    {
        $this->renderingData = $renderingData;
    }

    public function getBorder(): bool
    {
        return $this->border;
    }

    public function setBorder(bool $border): void
    {
        $this->border = $border;
    }

    public function enrichLayoutDefinition(?Concrete $object, array $context = []): static
    {
        $renderer = null;
        $class = $this->getRenderingClass();
        if (!empty($class)) {
            $renderer = Model\DataObject\ClassDefinition\Helper\DynamicTextResolver::resolveRenderingClass(
                $class
            );
        }

        $context['fieldname'] = $this->getName();
        $context['layout'] = $this;

        if ($renderer instanceof DynamicTextLabelInterface) {
            $result = $renderer->renderLayoutText($this->renderingData, $object, $context);
            $this->html = $result;
        }

        /** @var Sandbox $sandbox */
        $sandbox = OpenDxp::getContainer()->get('opendxp.templating.sandbox.html');

        try {
            $this->html = $sandbox
                ->createTemplate($this->html)
                ->render([
                    ...$context,
                    'object' => $object,
                ]);
        } catch (SecurityError $e) {
            Logger::err((string) $e);

            $this->html = sprintf('<h2>Error</h2>Failed rendering the template: <b>%s</b>.
                Please check your twig sandbox security policy or contact the administrator.',
                $e->getRawMessage());
        }

        return $this;
    }
}
