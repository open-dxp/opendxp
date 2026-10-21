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

namespace OpenDxp\Tests\Application\GenericExecutionEngine;

use RuntimeException;

final readonly class RetryOnceHandler
{
    public function __construct(private Notes $notes)
    {
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
