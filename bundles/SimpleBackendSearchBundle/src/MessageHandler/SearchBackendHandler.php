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

namespace OpenDxp\Bundle\SimpleBackendSearchBundle\MessageHandler;

use OpenDxp\Bundle\SimpleBackendSearchBundle\Message\SearchBackendMessage;
use OpenDxp\Bundle\SimpleBackendSearchBundle\Model\Search\Backend\Data;
use OpenDxp\Messenger\Handler\HandlerHelperTrait;
use OpenDxp\Model\Element;
use Symfony\Component\Messenger\Handler\Acknowledger;
use Symfony\Component\Messenger\Handler\BatchHandlerInterface;
use Symfony\Component\Messenger\Handler\BatchHandlerTrait;
use Throwable;

/**
 * @internal
 */
class SearchBackendHandler implements BatchHandlerInterface
{
    use BatchHandlerTrait;
    use HandlerHelperTrait;

    public function __invoke(SearchBackendMessage $message, ?Acknowledger $ack = null): mixed
    {
        return $this->handle($message, $ack);
    }

    private function process(array $jobs): void
    {
        $jobs = $this->filterUnique($jobs, static fn(SearchBackendMessage $message) => $message->getType() . '-' . $message->getId());

        /**
         * @var SearchBackendMessage $message
         * @var Acknowledger $ack
         */
        foreach ($jobs as [$message, $ack]) {
            try {
                $element = Element\Service::getElementById($message->getType(), $message->getId());
                if (!$element instanceof Element\ElementInterface) {
                    $ack->ack($message);

                    continue;
                }

                $searchEntry = Data::getForElement($element);
                if ($searchEntry->getId() instanceof Data\Id) {
                    $searchEntry->setDataFromElement($element);
                    $searchEntry->save();
                } else {
                    $searchEntry = new Data($element);
                    $searchEntry->save();
                }

                $ack->ack($message);
            } catch (Throwable $e) {
                $ack->nack($e);
            }
        }
    }

    private function shouldFlush(): bool
    {
        return 50 <= count($this->jobs);
    }
}
