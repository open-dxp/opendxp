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

use League\Flysystem\StorageAttributes;
use OpenDxp\Console\AbstractCommand;
use OpenDxp\Image\ImageOptimizerInterface;
use OpenDxp\Tool\Storage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'opendxp:thumbnails:optimize-images',
    description: 'Optimize filesize of all thumbnails',
    aliases: ['thumbnails:optimize-images']
)]
class OptimizeImageThumbnailsCommand extends AbstractCommand
{
    public function __construct(private readonly ImageOptimizerInterface $optimizer)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $storage = Storage::get('thumbnail');
        $savedBytesTotal = 0;

        /** @var StorageAttributes $item */
        foreach ($storage->listContents('/', true) as $item) {
            if ($item->isFile()) {
                $originalFilesize = $storage->fileSize($item->path());

                $this->optimizer->optimizeImage($item->path());

                clearstatcache();

                $savedBytes = ($originalFilesize - $storage->fileSize($item->path()));
                $savedBytesTotal += $savedBytes;

                $this->output->writeln('Optimized image: ' . $item->path() . ' saved ' . formatBytes($savedBytes));
            }
        }

        $this->output->writeln('Finished!');
        $this->output->writeln('Saved ' . formatBytes($savedBytesTotal) . ' in total');

        return 0;
    }
}
