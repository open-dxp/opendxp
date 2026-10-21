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

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Configuration;

/**
 * Gives an unknown context the defaults of the configuration. A job run can outlive the configuration of its context.
 *
 * @internal
 */
final readonly class ExecutionContext implements ExecutionContextInterface
{
    private const string DEFAULT_TRANSLATION_DOMAIN = 'admin';

    public function __construct(private array $contexts)
    {
    }

    public function hasContext(string $context): bool
    {
        return isset($this->contexts[$context]);
    }

    public function getTranslationDomain(string $context): string
    {
        return $this->contexts[$context]['translations_domain'] ?? self::DEFAULT_TRANSLATION_DOMAIN;
    }

    public function getErrorHandlingFromContext(string $context): ?string
    {
        return $this->contexts[$context]['error_handling'] ?? null;
    }

    public function isDisposable(string $context): bool
    {
        return $this->contexts[$context]['disposable'] ?? false;
    }

    /**
     * @return list<string>
     */
    public function getDisposableContexts(): array
    {
        return array_keys(array_filter(
            $this->contexts,
            static fn (array $context): bool => $context['disposable'] ?? false,
        ));
    }
}
