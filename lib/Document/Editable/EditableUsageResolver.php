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

namespace OpenDxp\Document\Editable;

use OpenDxp\Document\Renderer\DocumentRendererInterface;
use OpenDxp\Http\Request\Resolver\EditmodeResolver;
use OpenDxp\Model\Document;
use OpenDxp\Model\Document\Editable\Block;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
class EditableUsageResolver
{
    protected ?UsageRecorderSubscriber $subscriber = null;

    public function __construct(protected EventDispatcherInterface $dispatcher, protected DocumentRendererInterface $renderer)
    {
    }

    public function getUsedEditableNames(Document\PageSnippet $document): array
    {
        $this->registerEventSubscriber();

        // we render in editmode, so that we can ensure all elements that can be edited are present in the export
        // this is especially necessary when lazy loading certain elements on a page (eg. using ajax-include and similar solutions)
        $this->renderer->render($document, [
            EditmodeResolver::ATTRIBUTE_EDITMODE => true,
            Block::ATTRIBUTE_IGNORE_EDITMODE_INDICES => true,
            ]);
        $names = $this->subscriber->getRecordedEditableNames();
        $this->unregisterEventSubscriber();

        return array_unique($names);
    }

    protected function registerEventSubscriber(): void
    {
        if (!$this->subscriber) {
            $this->subscriber = new UsageRecorderSubscriber();
            $this->dispatcher->addSubscriber($this->subscriber);
        }
    }

    protected function unregisterEventSubscriber(): void
    {
        if ($this->subscriber) {
            $this->dispatcher->removeSubscriber($this->subscriber);
            $this->subscriber = null;
        }
    }
}
