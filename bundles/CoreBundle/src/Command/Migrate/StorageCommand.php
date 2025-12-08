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

namespace OpenDxp\Bundle\CoreBundle\Command\Migrate;

use Exception;
use League\Flysystem\StorageAttributes;
use OpenDxp\Console\AbstractCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'opendxp:migrate:storage',
    description: 'Migrate data from one storage to another'
)]
class StorageCommand extends AbstractCommand
{
    public function __construct(private readonly ContainerInterface $locator)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'storage',
                InputArgument::IS_ARRAY,
                'A list of storages to be migrated'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $errors = [];
        $storages = $input->getArgument('storage');

        foreach ($storages as $storageName) {
            $storageSourceName = $this->getStorageName($storageName, 'source');
            $storageTargetName = $this->getStorageName($storageName, 'target');

            try {
                $sourceStorage = $this->locator->get($storageSourceName);
                $targetStorage = $this->locator->get($storageTargetName);
            } catch (Exception $e) {
                $this->io->warning(sprintf('Skipped migrating storage "%s": please make sure "%s" and "%s" configuration exists.', $storageName, $storageSourceName, $storageTargetName));

                continue;
            }

            $this->io->newLine();
            $this->io->info(sprintf('Migrating storage "%s"', $storageName));

            $progressBar = new ProgressBar($output);
            $progressBar->setFormat('%current% [%bar%] %message%');
            $progressBar->start();

            /** @var StorageAttributes $item */
            foreach ($sourceStorage->listContents('/', true) as $item) {
                if ($item->isFile()) {
                    $path = $item->path();

                    try {
                        $stream = $sourceStorage->readStream($path);

                        if (!$targetStorage->fileExists($path)) {
                            $targetStorage->writeStream($item->path(), $stream);

                            $progressBar->setMessage(sprintf('Migrating %s: %s', $storageName, $item->path()));
                        } else {
                            $progressBar->setMessage(sprintf('Skipping %s: %s', $storageName, $item->path()));
                        }
                    } catch (Exception $e) {
                        $progressBar->setMessage(sprintf('Skipping %s: %s', $storageName, $item->path()));
                        $errors[] = $e->getMessage();
                    }
                    $progressBar->advance();
                }
            }

            $progressBar->finish();
        }

        $this->io->success('Finished Migrating Storage!');

        if ($errors) {
            $this->io->warning('Some errors occoured during migrating certain files:');
            $this->io->writeLn(implode("\n", $errors));
        }

        return 0;
    }

    public function getStorageName(string $name, string $type): string
    {
        return sprintf('opendxp.%s.storage.%s', $name, $type);
    }
}
