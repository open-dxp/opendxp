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

namespace OpenDxp\Bundle\SeoBundle\Redirect;

/**
 * The outcome of checking a redirect. Messages are translation keys, so the admin shows them in the language of the
 * editor.
 *
 * @internal
 */
final readonly class RedirectValidation
{
    /**
     * @param list<array{field: string, message: string}>                     $errors
     * @param list<array{message: string, parameters: array<string, string|int>}> $warnings
     */
    public function __construct(
        public array $errors = [],
        public array $warnings = [],
    ) {
    }

    public function withError(string $field, string $message): self
    {
        return new self([...$this->errors, ['field' => $field, 'message' => $message]], $this->warnings);
    }

    /**
     * @param array<string, string|int> $parameters
     */
    public function withWarning(string $message, array $parameters = []): self
    {
        return new self($this->errors, [...$this->warnings, ['message' => $message, 'parameters' => $parameters]]);
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }
}
