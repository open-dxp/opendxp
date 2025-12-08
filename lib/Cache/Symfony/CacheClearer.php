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

namespace OpenDxp\Cache\Symfony;

use Closure;
use OpenDxp\Tool\Console;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * @internal
 */
class CacheClearer
{
    private int $processTimeout;

    private ?Closure $runCallback = null;

    public function __construct(array $options = [])
    {
        $this->resolveOptions($options);
    }

    private function resolveOptions(array $options = []): void
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'processTimeout' => 300,
        ]);

        $resolver->setAllowedTypes('processTimeout', 'int');
        $resolver->setRequired('processTimeout');

        $options = $resolver->resolve($options);

        $this->processTimeout = $options['processTimeout'];
    }

    public function clear(string $environment, array $options = []): Process
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'no-warmup' => false,
            'no-optional-warmers' => false,
            'env' => $environment,
            'ansi' => false,
            'no-ansi' => false,
        ]);

        foreach (['no-warmup', 'no-optional-warmers', 'ansi', 'no-ansi'] as $option) {
            $resolver->setAllowedTypes($option, 'bool');
        }

        return $this->runCommand('cache:clear', $resolver->resolve($options));
    }

    public function warmup(string $environment, array $options = []): Process
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'no-optional-warmers' => false,
            'env' => $environment,
            'ansi' => false,
            'no-ansi' => false,
        ]);

        foreach (['no-optional-warmers', 'ansi', 'no-ansi'] as $option) {
            $resolver->setAllowedTypes($option, 'bool');
        }

        return $this->runCommand('cache:warmup', $resolver->resolve($options));
    }

    public function setRunCallback(?Closure $runCallback = null): void
    {
        $this->runCallback = $runCallback;
    }

    private function runCommand(string $command, array $arguments = []): Process
    {
        $process = $this->buildProcess($command, $arguments);
        $process->run($this->runCallback);

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process;
    }

    private function buildProcess(string $command, array $arguments = []): Process
    {
        $preparedOptions = [];
        foreach ($arguments as $optionKey => $optionValue) {
            if ($optionValue === false) {
                continue;
            }
            if ($optionValue === null) {
                continue;
            }
            $preparedOptions[] = '--' . $optionKey . (($optionValue === true) ? '' : '=' . $optionValue);
        }

        $cmd = [Console::getPhpCli(), 'bin/console', $command, ...$preparedOptions];

        $process = new Process($cmd);
        $process
            ->setTimeout($this->processTimeout)
            ->setWorkingDirectory(OPENDXP_PROJECT_ROOT);

        return $process;
    }
}
