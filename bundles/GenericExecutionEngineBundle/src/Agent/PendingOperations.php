<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Agent;

use Doctrine\DBAL\Connection;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Utils\Constants\TableConstants;
use Symfony\Component\Clock\Clock;

/**
 * Writes in the connection of the code that dispatches. With the Doctrine transport, a message and its count share one
 * transaction.
 *
 * @internal
 */
final readonly class PendingOperations
{
    public function __construct(
        private Connection $db,
    ) {
    }

    public function addMessages(int $jobRunId, int $count): void
    {
        $this->db->executeStatement(
            sprintf(
                'UPDATE %s SET pendingOperations = pendingOperations + ?, dispatchedMessages = dispatchedMessages + ?,'
                . ' modificationDate = ? WHERE id = ?',
                TableConstants::JOB_RUN_TABLE,
            ),
            [$count, $count, Clock::get()->now()->getTimestamp(), $jobRunId],
        );
    }

    /**
     * @return bool true when the job run waits for nothing more
     */
    public function remove(int $jobRunId): bool
    {
        return $this->removeOperation($jobRunId, succeeded: 0, failed: 0);
    }

    /**
     * @return bool true when the job run waits for nothing more
     */
    public function removeHandledMessage(int $jobRunId): bool
    {
        return $this->removeOperation($jobRunId, succeeded: 1, failed: 0);
    }

    /**
     * @return bool true when the job run waits for nothing more
     */
    public function removeFailedMessage(int $jobRunId): bool
    {
        return $this->removeOperation($jobRunId, succeeded: 0, failed: 1);
    }

    private function removeOperation(int $jobRunId, int $succeeded, int $failed): bool
    {
        $this->db->executeStatement(
            sprintf(
                'UPDATE %s SET pendingOperations = pendingOperations - 1,'
                . ' processedElementsForStep = processedElementsForStep + ?,'
                . ' succeededMessages = succeededMessages + ?, failedMessages = failedMessages + ?,'
                . ' modificationDate = ? WHERE id = ?',
                TableConstants::JOB_RUN_TABLE,
            ),
            [$succeeded + $failed, $succeeded, $failed, Clock::get()->now()->getTimestamp(), $jobRunId],
        );

        $pendingOperations = $this->db->fetchOne(
            sprintf('SELECT pendingOperations FROM %s WHERE id = ?', TableConstants::JOB_RUN_TABLE),
            [$jobRunId],
        );

        return $pendingOperations !== false && (int) $pendingOperations === 0;
    }
}
