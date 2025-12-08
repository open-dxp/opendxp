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

namespace OpenDxp\Bundle\CoreBundle\Command\Bundle;

use InvalidArgumentException;
use OpenDxp\Console\AbstractCommand;
use OpenDxp\Extension\Bundle\OpenDxpBundleInterface;
use OpenDxp\Extension\Bundle\OpenDxpBundleManager;
use Symfony\Component\Console\Input\InputOption;

/**
 * @internal
 */
abstract class AbstractBundleCommand extends AbstractCommand
{
    public function __construct(protected OpenDxpBundleManager $bundleManager, ?string $name = null)
    {
        parent::__construct($name);
    }

    /**
     * @return $this
     */
    protected function configureDescriptionAndHelp(string $description, ?string $help = null): static
    {
        if (null === $help) {
            $help = 'Bundle can be passed as fully qualified class name or as bundle short name (e.g. <comment>OpenDxpApplicationLoggerBundle</comment>).';
        }

        $this
            ->setDescription($description)
            ->setHelp(sprintf('%s. %s', $description, $help));

        return $this;
    }

    /**
     * @return $this
     */
    protected function configureFailWithoutErrorOption(): static
    {
        $this->addOption(
            'fail-without-error',
            'f',
            InputOption::VALUE_NONE,
            'Just output a warning but do not return an error code if the command can\'t be executed'
        );

        return $this;
    }

    protected function buildName(string $name): string
    {
        return sprintf('opendxp:bundle:%s', $name);
    }

    protected function handlePrerequisiteError(string $message): int
    {
        if ($this->io->getInput()->getOption('fail-without-error')) {
            $this->io->warning($message);

            return 0;
        }
        $this->io->error($message);

        return 1;
    }

    protected function getBundle(): OpenDxpBundleInterface
    {
        $bundleId = $this->io->getInput()->getArgument('bundle');
        $bundleId = $this->normalizeBundleIdentifier($bundleId);

        $activeBundles = $this->bundleManager->getActiveBundles(false);

        $bundle = null;

        if (isset($activeBundles[$bundleId])) {
            // try to load bundle via fully qualified class name first
            $bundle = $activeBundles[$bundleId];
        } else {
            // fall back to fetching bundle from kernel with its logical name
            $kernel = $this->getApplication()->getKernel();
            $bundle = $kernel->getBundle($bundleId);
        }

        if (!$bundle instanceof OpenDxpBundleInterface) {
            throw new InvalidArgumentException(sprintf(
                'Bundle "%s" does not implement %s',
                $bundle->getName(),
                OpenDxpBundleInterface::class
            ));
        }

        return $bundle;
    }

    protected function setupInstaller(OpenDxpBundleInterface $bundle): ?\OpenDxp\Extension\Bundle\Installer\InstallerInterface
    {
        return $this->bundleManager->getInstaller($bundle);
    }

    protected function normalizeBundleIdentifier(string $bundleIdentifier): string
    {
        return str_replace('/', '\\', $bundleIdentifier);
    }

    protected function getShortClassName(string $className): ?string
    {
        if (!class_exists($className)) {
            throw new InvalidArgumentException(sprintf('Class "%s" does not exist', $className));
        }

        $parts = explode('\\', $className);

        return array_pop($parts);
    }
}
