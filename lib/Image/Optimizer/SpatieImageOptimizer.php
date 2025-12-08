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

namespace OpenDxp\Image\Optimizer;

use OpenDxp\Exception\ImageOptimizationFailedException;
use Spatie\ImageOptimizer\OptimizerChain;
use Spatie\ImageOptimizer\Optimizers\Cwebp;
use Spatie\ImageOptimizer\Optimizers\Jpegoptim;
use Spatie\ImageOptimizer\Optimizers\Optipng;
use Spatie\ImageOptimizer\Optimizers\Pngquant;

final class SpatieImageOptimizer implements \OpenDxp\Image\Optimizer\OptimizerInterface
{
    public function optimizeImage(string $input, string $output): string
    {
        $optimizerChain = (new OptimizerChain)
            ->addOptimizer(new Jpegoptim([
                '--strip-all',
                '--all-progressive',
            ]))
            ->addOptimizer(new Pngquant)
            ->addOptimizer(new Optipng)
            ->addOptimizer(new Cwebp([
                '-pass 10',
                '-mt',
            ]));

        $optimizerChain->optimize($input, $output); // To keep original image untouched and create the optimized one as a new image

        if (file_exists($output) && filesize($output) > 0) {
            return $output;
        }

        throw new ImageOptimizationFailedException('Could not create optimized image');
    }

    public function supports(string $mimeType): bool
    {
        //  Implement supports() method.
        return in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true);
    }
}
