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

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Configuration;

use OpenDxp\Bundle\GenericExecutionEngineBundle\Exception\ExecutionContextNotDefinedException;

/**
 * @internal
 */
final readonly class ExecutionContext implements ExecutionContextInterface
{
    public function __construct(
        private array $contexts
    ) {
    }

    public function getTranslationDomain(string $context): string
    {
        $this->validateContext($context);

        return $this->contexts[$context]['translations_domain'];
    }

    public function getErrorHandlingFromContext(string $context): ?string
    {
        $this->validateContext($context);

        return $this->contexts[$context]['error_handling'] ?? null;
    }

    private function validateContext(string $context): void
    {
        if (!isset($this->contexts[$context])) {
            throw new ExecutionContextNotDefinedException(
                sprintf('Execution context "%s" is not defined.', $context)
            );
        }
    }
}
