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

namespace OpenDxp\Messenger\Handler;

use Exception;
use OpenDxp\Logger;
use OpenDxp\Messenger\VersionDeleteMessage;
use OpenDxp\Model\Version;
use Symfony\Component\Messenger\Handler\Acknowledger;
use Symfony\Component\Messenger\Handler\BatchHandlerInterface;
use Symfony\Component\Messenger\Handler\BatchHandlerTrait;
use Throwable;

/**
 * @internal
 */
class VersionDeleteHandler implements BatchHandlerInterface
{
    use BatchHandlerTrait;

    public function __invoke(VersionDeleteMessage $message, ?Acknowledger $ack = null): mixed
    {
        return $this->handle($message, $ack);
    }

    private function process(array $jobs): void
    {
        foreach ($jobs as [$message, $ack]) {
            try {
                $versions = new Version\Listing();
                $versions->setCondition('cid = :cid AND ctype = :ctype', [
                    'cid' => $message->getElementId(),
                    'ctype' => $message->getElementType(),
                ]);

                foreach ($versions as $version) {
                    try {
                        $version->delete();
                    } catch (Exception $e) {
                        Logger::err(sprintf('Problem deleting the version with Id: %s, reason: %s', $version->getId(), $e->getMessage()));
                    }
                }

                $ack->ack($message);
            } catch (Throwable $e) {
                $ack->nack($e);
            }
        }
    }
}
