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

namespace OpenDxp\Bundle\SeoBundle\Redirect;

/**
 * Holds the errors and warnings of a redirect check. Their messages are translation keys, so the admin shows them in
 * the language of the editor.
 *
 * @internal
 */
final readonly class RedirectValidationResult
{
    /**
     * @param list<RedirectValidationError>   $errors
     * @param list<RedirectValidationWarning> $warnings
     */
    public function __construct(
        public array $errors = [],
        public array $warnings = [],
    ) {
    }

    public function withError(string $field, string $message): self
    {
        return new self([...$this->errors, new RedirectValidationError($field, $message)], $this->warnings);
    }

    /**
     * @param array<string, string|int> $parameters
     */
    public function withWarning(string $message, array $parameters = []): self
    {
        return new self($this->errors, [...$this->warnings, new RedirectValidationWarning($message, $parameters)]);
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }
}
