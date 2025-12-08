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

namespace OpenDxp\Console\Traits;

use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputOption;

/**
 * @internal
 */
trait DryRun
{
    /**
     * Configure --dry-run
     *
     *
     * @return $this
     */
    protected function configureDryRunOption(?string $description = null): static
    {
        if (null === $description) {
            $description = 'Simulate only (do not change anything)';
        }

        $this->addOption(
            'dry-run',
            'N',
            InputOption::VALUE_NONE,
            $description
        );

        return $this;
    }

    protected function isDryRun(): bool
    {
        /** @var Input $input */
        $input = $this->input;

        return (bool) $input->getOption('dry-run');
    }

    /**
     * Prefix message with DRY-RUN
     */
    protected function prefixDryRun(string $message, string $prefix = 'DRY-RUN'): string
    {
        return sprintf(
            '<bg=cyan;fg=white>%s</> %s',
            $prefix,
            $message
        );
    }

    /**
     * Prefix message with dry run if in dry-run mode
     */
    protected function dryRunMessage(string $message, string $prefix = 'DRY-RUN'): string
    {
        if ($this->isDryRun()) {
            return $this->prefixDryRun($message, $prefix);
        }

        return $message;
    }
}
