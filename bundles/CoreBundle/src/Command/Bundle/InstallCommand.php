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

use Exception;
use OpenDxp\Bundle\CoreBundle\Command\Bundle\Helper\PostStateChange;
use OpenDxp\Extension\Bundle\OpenDxpBundleManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
class InstallCommand extends AbstractBundleCommand
{
    public function __construct(OpenDxpBundleManager $bundleManager, private readonly PostStateChange $postStateChangeHelper)
    {
        parent::__construct($bundleManager);
    }

    protected function configure(): void
    {
        $this
            ->setName($this->buildName('install'))
            ->configureDescriptionAndHelp('Installs a bundle')
            ->addArgument('bundle', InputArgument::REQUIRED, 'The bundle to install')
            ->configureFailWithoutErrorOption()
        ;

        PostStateChange::configureStateChangeCommandOptions($this);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $bundle = $this->getBundle();

        if ($this->bundleManager->isInstalled($bundle)) {
            $this->io->success(sprintf('Bundle "%s" is already installed', $bundle->getName()));

            return Command::SUCCESS;
        }

        // sets up installer with console output writer
        $this->setupInstaller($bundle);

        try {
            $this->bundleManager->install($bundle);

            $this->io->success(sprintf('Bundle "%s" was successfully installed', $bundle->getName()));
        } catch (Exception $e) {
            return $this->handlePrerequisiteError($e->getMessage());
        }

        $this->postStateChangeHelper->runPostStateChangeCommands(
            $this->io,
            $this->getApplication()->getKernel()->getEnvironment()
        );

        return Command::SUCCESS;
    }
}
