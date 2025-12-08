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

namespace OpenDxp\Messenger\Handler;

use OpenDxp\Messenger\CleanupThumbnailsMessage;
use OpenDxp\Model\Asset;
use Symfony\Component\Messenger\Handler\Acknowledger;
use Symfony\Component\Messenger\Handler\BatchHandlerInterface;
use Symfony\Component\Messenger\Handler\BatchHandlerTrait;
use Throwable;

/**
 * @internal
 */
class CleanupThumbnailsHandler implements BatchHandlerInterface
{
    use BatchHandlerTrait;
    use HandlerHelperTrait;

    public function __invoke(CleanupThumbnailsMessage $message, ?Acknowledger $ack = null): mixed
    {
        return $this->handle($message, $ack);
    }

    // @phpstan-ignore-next-line
    private function process(array $jobs): void
    {
        $jobs = $this->filterUnique($jobs, static fn (CleanupThumbnailsMessage $message) => $message->getType() . '-' . $message->getName());

        foreach ($jobs as [$message, $ack]) {
            try {
                $configClass = 'OpenDxp\Model\Asset\\' . ucfirst($message->getType()) . '\Thumbnail\Config';
                /** @var Asset\Image\Thumbnail\Config|Asset\Video\Thumbnail\Config $thumbConfig */
                $thumbConfig = new $configClass();
                $thumbConfig->setName($message->getName());
                $thumbConfig->clearTempFiles();

                $ack->ack($message);
            } catch (Throwable $e) {
                $ack->nack($e);
            }
        }
    }
}
