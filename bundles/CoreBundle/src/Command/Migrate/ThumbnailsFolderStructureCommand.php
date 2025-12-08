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

use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;
use OpenDxp\Console\AbstractCommand;
use OpenDxp\Tool\Storage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'opendxp:migrate:thumbnails-folder-structure',
    description: 'Change thumbnail folder structure to
    <asset storage>/<path to asset>/<asset id>/image-thumb__<asset id>__<thumbnail name>/<asset filename>
    instead of
    <asset storage>/<path to asset>/image-thumb__<asset id>__<thumbnail name>/<asset filename>'
)]
class ThumbnailsFolderStructureCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $thumbnailStorage = Storage::get('thumbnail');

        $output->writeln('Migrating thumbnails ...');
        $this->doMigrateStorage($output, $thumbnailStorage);
        $output->writeln("\nSuccessfully moved thumbnail files to new folder structure\n");

        $assetCacheStorage = Storage::get('asset_cache');

        $output->writeln('Migrating asset cache (document previews, etc.) ...');
        $this->doMigrateStorage($output, $assetCacheStorage);
        $output->writeln("\nSuccessfully moved asset cache files to new folder structure");

        return 0;
    }

    protected function doMigrateStorage(OutputInterface $output, FilesystemOperator $storage): void
    {
        $thumbnailFiles = $storage->listContents('/', true)->filter(function (StorageAttributes $attributes) {
            if ($attributes->isDir()) {
                return false;
            }

            $matches = [];
            preg_match('/(image-thumb|video-thumb|pdf-thumb)__(\d+)__/', $attributes->path(), $matches);

            return count($matches) > 2 && !str_contains('/' . $attributes->path(), '/'.$matches[2].'/' . $matches[1] . '__'.$matches[2].'__');
        });

        $iterator = $thumbnailFiles->toArray();
        $progressBar = new ProgressBar($output, count($iterator));

        $progressBar->start();

        /** @var StorageAttributes $thumbnailFile */
        foreach ($iterator as $thumbnailFile) {
            $targetPath = preg_replace('/(image-thumb|video-thumb|pdf-thumb)__(\d+)__(.+)$/', '$2/$1__$2__$3', $thumbnailFile->path());

            if (!$storage->fileExists($targetPath)) {
                $storage->move($thumbnailFile->path(), $targetPath);
            } elseif ($thumbnailFile->isDir()) {
                $storage->deleteDirectory($thumbnailFile->path());
            } else {
                $storage->delete($thumbnailFile->path());
            }

            $progressBar->advance();
        }
        $progressBar->finish();
    }
}
