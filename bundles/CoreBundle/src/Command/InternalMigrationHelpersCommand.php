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

namespace OpenDxp\Bundle\CoreBundle\Command;

use Doctrine\Migrations\DependencyFactory;
use OpenDxp;
use OpenDxp\Console\AbstractCommand;
use OpenDxp\Migrations\FilteredTableMetadataStorage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * @internal
 */
#[AsCommand(
    name: 'internal:migration-helpers',
    description: 'For internal use only',
    hidden: true
)]
class InternalMigrationHelpersCommand extends AbstractCommand
{
    public function __construct(private readonly DependencyFactory $dependencyFactory, private readonly FilteredTableMetadataStorage $metadataStorage, ?string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'is-installed',
                null,
                InputOption::VALUE_NONE,
                'Checks whether OpenDXP is already installed or not'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('is-installed')) {
            try {
                if (OpenDxp::isInstalled()) {
                    $this->metadataStorage->__invoke($this->dependencyFactory);
                    $this->metadataStorage->ensureInitialized();
                    $output->write('1');
                }
            } catch (Throwable) {
                // nothing to do
            }
        }

        return 0;
    }
}
