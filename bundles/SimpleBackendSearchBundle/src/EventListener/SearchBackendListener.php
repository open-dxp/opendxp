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

namespace OpenDxp\Bundle\SimpleBackendSearchBundle\EventListener;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\SimpleBackendSearchBundle\Message\SearchBackendMessage;
use OpenDxp\Bundle\SimpleBackendSearchBundle\Model\Search\Backend\Data;
use OpenDxp\Event\AssetEvents;
use OpenDxp\Event\DataObjectEvents;
use OpenDxp\Event\DocumentEvents;
use OpenDxp\Event\Model\AssetEvent;
use OpenDxp\Event\Model\ElementEventInterface;
use OpenDxp\Model\DataObject\Listing;
use OpenDxp\Model\Element\Service;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
class SearchBackendListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly MessageBusInterface $messengerBusOpendxpCore
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectEvents::POST_ADD => 'onPostAddUpdateElement',
            DocumentEvents::POST_ADD => 'onPostAddUpdateElement',
            AssetEvents::POST_ADD => 'onPostAddUpdateElement',

            DataObjectEvents::PRE_DELETE => 'onPreDeleteElement',
            DocumentEvents::PRE_DELETE => 'onPreDeleteElement',
            AssetEvents::PRE_DELETE => 'onPreDeleteElement',

            DataObjectEvents::POST_UPDATE => 'onPostAddUpdateElement',
            DocumentEvents::POST_UPDATE => 'onPostAddUpdateElement',
            AssetEvents::POST_UPDATE => 'onPostAddUpdateElement',

            AdminEvents::OBJECT_LIST_HANDLE_FULLTEXT_QUERY => 'onHandleFulltextQuery',
        ];
    }

    public function onPostAddUpdateElement(ElementEventInterface $e): void
    {
        //do not update index when auto save or only saving version
        if (
            !$e instanceof AssetEvent &&
            (($e->hasArgument('isAutoSave') && $e->getArgument('isAutoSave')) ||
                ($e->hasArgument('saveVersionOnly') && $e->getArgument('saveVersionOnly')))
        ) {
            return;
        }

        $element = $e->getElement();
        $this->messengerBusOpendxpCore->dispatch(
            new SearchBackendMessage(Service::getElementType($element), $element->getId())
        );
    }

    public function onPreDeleteElement(ElementEventInterface $e): void
    {
        $searchEntry = Data::getForElement($e->getElement());
        if ($searchEntry->getId() instanceof Data\Id) {
            $searchEntry->delete();
        }
    }

    public function onHandleFulltextQuery(GenericEvent $e): void
    {
        $query = $e->getArgument('query');
        /** @var Listing $list */
        $list = $e->getArgument('list');
        $e->setArgument(
            'condition',
            'oo_id IN (SELECT id FROM search_backend_data WHERE maintype = "object" AND MATCH (`data`,`properties`) AGAINST (' . $list->quote($query) . ' IN BOOLEAN MODE))'
        );
    }
}
