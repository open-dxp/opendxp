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

namespace OpenDxp\Image;

use InvalidArgumentException;
use OpenDxp\Exception\ImageOptimizationFailedException;
use OpenDxp\File;
use OpenDxp\Image\Optimizer\OptimizerInterface;
use OpenDxp\Tool\Storage;

class Optimizer implements ImageOptimizerInterface
{
    /**
     * @var OptimizerInterface[]
     */
    private array $optimizers = [];

    public function optimizeImage(string $path): void
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $storage = Storage::get('thumbnail');
        $optimizedImages = [];
        $workingPath = File::getLocalTempFilePath($extension);
        file_put_contents($workingPath, $storage->read($path));

        foreach ($this->optimizers as $optimizer) {
            if ($optimizer->supports($storage->mimeType($path))) {
                $tmpFilePath = File::getLocalTempFilePath($extension);

                try {
                    $optimizedFile = $optimizer->optimizeImage($workingPath, $tmpFilePath);

                    $optimizedImages[] = [
                        'filesize' => filesize($optimizedFile),
                        'path' => $optimizedFile,
                        'optimizer' => $optimizer,
                    ];
                } catch (ImageOptimizationFailedException) {
                }
            }
        }

        // order by filesize
        usort($optimizedImages, fn ($a, $b) => $a['filesize'] <=> $b['filesize']);

        // first entry is the smallest -> use this one
        if (count($optimizedImages)) {
            $storage->write($path, file_get_contents($optimizedImages[0]['path']));
        }

        // cleanup
        foreach ($optimizedImages as $tmpFile) {
            unlink($tmpFile['path']);
        }
    }

    public function registerOptimizer(OptimizerInterface $optimizer): void
    {
        if (in_array($optimizer, $this->optimizers)) {
            throw new InvalidArgumentException(sprintf('Optimizer of class %s has already been registered',
                $optimizer::class));
        }

        $this->optimizers[] = $optimizer;
    }
}
