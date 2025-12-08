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

namespace OpenDxp\Bundle\UuidBundle\EventListener;

use OpenDxp;
use OpenDxp\Bundle\UuidBundle\Model\Tool\UUID;
use OpenDxp\Bundle\UuidBundle\OpenDxpUuidBundle;
use OpenDxp\Event\AssetEvents;
use OpenDxp\Event\DataObjectClassDefinitionEvents;
use OpenDxp\Event\DataObjectEvents;
use OpenDxp\Event\DocumentEvents;
use OpenDxp\Event\Model\DataObject\ClassDefinitionEvent;
use OpenDxp\Event\Model\ElementEventInterface;
use OpenDxp\Model\DataObject\ClassDefinitionInterface;
use OpenDxp\Model\Element\ElementInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @internal
 */
class UUIDListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectEvents::POST_ADD => 'onPostAdd',
            DocumentEvents::POST_ADD => 'onPostAdd',
            AssetEvents::POST_ADD => 'onPostAdd',
            DataObjectClassDefinitionEvents::POST_ADD => 'onPostAdd',

            DataObjectEvents::POST_DELETE => 'onPostDelete',
            DocumentEvents::POST_DELETE => 'onPostDelete',
            AssetEvents::POST_DELETE => 'onPostDelete',
            DataObjectClassDefinitionEvents::POST_DELETE => 'onPostDelete',
        ];
    }

    public function onPostAdd(Event $e): void
    {
        if ($this->isEnabled()) {
            $element = $this->extractElement($e);

            if ($element) {
                UUID::create($element);
            }
        }
    }

    public function onPostDelete(Event $e): void
    {
        if ($this->isEnabled()) {
            $element = $this->extractElement($e);

            if ($element) {
                $uuidObject = UUID::getByItem($element);
                $uuidObject->delete();
            }
        }
    }

    protected function isEnabled(): bool
    {
        if (!OpenDxpUuidBundle::isInstalled()) {
            return false;
        }

        $config = OpenDxp::getKernel()->getContainer()->getParameter('opendxp_uuid.instance_identifier');
        return !empty($config);
    }

    protected function extractElement(Event $event): ClassDefinitionInterface|ElementInterface|null
    {
        if ($event instanceof ElementEventInterface) {
            return $event->getElement();
        }
        if ($event instanceof ClassDefinitionEvent) {
            return $event->getClassDefinition();
        }
        return null;
    }
}
