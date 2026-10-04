<?php

declare(strict_types=1);

use OpenDxp\Db;
use OpenDxp\Messenger\Handler\SanityCheckHandler;
use OpenDxp\Messenger\SanityCheckMessage;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\TestFoundation\Container;
use Symfony\Component\Messenger\Handler\Acknowledger;

function referencing(ElementInterface $source, Asset ...$targets): ElementInterface
{
    foreach (array_values($targets) as $index => $target) {
        $source->setProperty('related' . $index, 'asset', $target);
    }

    $source->save();

    return $source;
}

function dependenciesOn(Asset $target): int
{
    return (int) Db::get()->fetchOne(
        'SELECT COUNT(*) FROM dependencies WHERE targettype = ? AND targetid = ?',
        [
            'asset',
            $target->getId(),
        ],
    );
}

/**
 * Runs the queued sanity checks as a worker of the queue opendxp_core does.
 */
function runSanityChecks(): void
{
    $transport = Container::get('messenger.transport.opendxp_core');
    $handler = Container::get(SanityCheckHandler::class);

    while ([] !== $envelopes = iterator_to_array($transport->get())) {
        $envelope = $envelopes[0];
        $transport->ack($envelope);

        if ($envelope->getMessage() instanceof SanityCheckMessage) {
            $handler($envelope->getMessage(), new Acknowledger(SanityCheckHandler::class));
        }
    }

    $handler->flush(true);
}

/**
 * Writes dependencies on elements that do not exist.
 *
 * @param array<string, int|string> $row
 */
function orphanedDependencies(string $column, int $count, array $row): void
{
    for ($i = 1; $i <= $count; $i++) {
        Db::get()->insert('dependencies', [
            ...$row,
            $column => 900000000 + $i,
        ]);
    }
}

/**
 * @param list<array<string, mixed>> $dependencies
 *
 * @return list<int>
 */
function dependencyIds(array $dependencies): array
{
    return array_map(static fn (array $dependency): int => (int) $dependency['id'], $dependencies);
}

/**
 * @param ElementInterface[] $elements
 *
 * @return list<int>
 */
function elementIds(array $elements): array
{
    return array_map(static fn (ElementInterface $element): int => $element->getId(), array_values($elements));
}
