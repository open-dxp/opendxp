<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Application\GenericExecutionEngine;

use RuntimeException;

final readonly class RetryOnceHandler
{
    public function __construct(
        private Notes $notes,
    ) {
    }

    public function __invoke(RetryOnceMessage $message): void
    {
        if (!$this->notes->has($message->name)) {
            $this->notes->add($message->name, 'failed once');

            throw new RuntimeException(sprintf('The message %s fails at its first attempt.', $message->name));
        }

        $this->notes->add($message->name, 'done');
    }
}
