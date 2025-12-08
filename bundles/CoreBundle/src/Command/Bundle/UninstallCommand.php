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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
class UninstallCommand extends AbstractBundleCommand
{
    public function __construct(OpenDxpBundleManager $bundleManager, private readonly PostStateChange $postStateChangeHelper)
    {
        parent::__construct($bundleManager);
    }

    protected function configure(): void
    {
        $this
            ->setName($this->buildName('uninstall'))
            ->configureDescriptionAndHelp('Uninstalls a bundle')
            ->addArgument('bundle', InputArgument::REQUIRED, 'The bundle to uninstall')
            ->configureFailWithoutErrorOption();

        PostStateChange::configureStateChangeCommandOptions($this);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $bundle = $this->getBundle();

        // sets up installer with console output writer
        $this->setupInstaller($bundle);

        try {
            $this->bundleManager->uninstall($bundle);

            $this->io->success(sprintf('Bundle "%s" was successfully uninstalled', $bundle->getName()));
        } catch (Exception $e) {
            return $this->handlePrerequisiteError($e->getMessage());
        }

        $this->postStateChangeHelper->runPostStateChangeCommands(
            $this->io,
            $this->getApplication()->getKernel()->getEnvironment()
        );

        return 0;
    }
}
