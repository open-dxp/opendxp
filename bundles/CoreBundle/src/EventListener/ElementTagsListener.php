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

namespace OpenDxp\Bundle\CoreBundle\EventListener;

use OpenDxp\Event\AssetEvents;
use OpenDxp\Event\DataObjectEvents;
use OpenDxp\Event\DocumentEvents;
use OpenDxp\Event\Model\AssetEvent;
use OpenDxp\Event\Model\ElementEventInterface;
use OpenDxp\Model\Element\ElementType;
use OpenDxp\Model\Element\Service;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
class ElementTagsListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectEvents::POST_COPY => 'onPostCopy',
            DocumentEvents::POST_COPY => 'onPostCopy',
            AssetEvents::POST_COPY => 'onPostCopy',

            AssetEvents::POST_DELETE => ['onPostAssetDelete', -9999],
        ];
    }

    public function onPostCopy(ElementEventInterface $e): void
    {
        $elementType = Service::getElementType($e->getElement());
        $copiedElement = $e->getElement();
        /** @var \OpenDxp\Model\Element\ElementInterface $baseElement */
        $baseElement = $e->getArgument('base_element');
        \OpenDxp\Model\Element\Tag::setTagsForElement(
            $elementType,
            $copiedElement->getId(),
            \OpenDxp\Model\Element\Tag::getTagsForElement($elementType, $baseElement->getId())
        );
    }

    public function onPostAssetDelete(AssetEvent $e): void
    {
        $asset = $e->getAsset();
        \OpenDxp\Model\Element\Tag::setTagsForElement(ElementType::Asset->value, $asset->getId(), []);
    }
}
