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

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Messenger\Middleware;

use OpenDxp\Bundle\GenericExecutionEngineBundle\Extractor\JobRunExtractorInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Messenger\Messages\GenericExecutionEngineMessageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

/**
 * @internal
 */
final readonly class StepConditionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private JobRunExtractorInterface $jobRunExtractor,
        private LoggerInterface $genericExecutionEngineLogger,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        if ($message instanceof GenericExecutionEngineMessageInterface) {
            if ($this->jobRunExtractor->checkCondition($message)) {
                return $stack->next()->handle($envelope, $stack);
            }

            $this->genericExecutionEngineLogger->info(sprintf(
                '[JobRun %d]: Skipping a message of step %d. The condition of the step is no longer met.',
                $message->getJobRunId(),
                $message->getCurrentJobStep(),
            ));

            return $envelope;
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
