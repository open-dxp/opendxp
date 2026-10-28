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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

use OpenDxp\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\Job;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Utils\Enums\SelectionProcessingMode;
use OpenDxp\Model\Element\ElementDescriptor;
use OpenDxp\Model\Element\ElementType;
use OpenDxp\TestFoundation\Container;
use OpenDxp\Tests\Application\GenericExecutionEngine\CountStepsMessage;
use OpenDxp\Tests\Application\GenericExecutionEngine\NoteElementMessage;

/**
 * @param list<int> $elementIds
 * @param list<JobStep> $steps
 */
function createJob(array $elementIds, array $steps): Job
{
    return new Job(
        'note-elements',
        $steps,
        array_map(static fn (int $id): ElementDescriptor => new ElementDescriptor(ElementType::Object->value, $id), $elementIds),
    );
}

/**
 * @param list<int> $failFor
 */
function createNoteStep(
    array $failFor = [],
    string $condition = '',
    SelectionProcessingMode $mode = SelectionProcessingMode::FOR_EACH,
): JobStep {
    return new JobStep('Note the elements', NoteElementMessage::class, $condition, ['failFor' => $failFor], $mode);
}

/**
 * @param array<string, mixed> $configuration
 */
function createCountJob(array $configuration): Job
{
    return new Job(
        'count-steps',
        [new JobStep('Count the steps', CountStepsMessage::class, '', $configuration, SelectionProcessingMode::ONCE)],
    );
}

function findLatestJobRun(): ?JobRun
{
    $jobRuns = Container::get(JobRunRepositoryInterface::class)
        ->getJobRunsByUserId(orderBy: ['id' => 'DESC'], limit: 1);

    return $jobRuns[0] ?? null;
}

/**
 * @return resource
 */
function createArtifactStream(string $content)
{
    $stream = fopen('php://memory', 'r+b');
    fwrite($stream, $content);
    rewind($stream);

    return $stream;
}
