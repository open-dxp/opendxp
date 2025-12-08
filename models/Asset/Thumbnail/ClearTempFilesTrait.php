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

namespace OpenDxp\Model\Asset\Thumbnail;

use League\Flysystem\StorageAttributes;
use OpenDxp\Tool\Storage;

/**
 * @internal
 */
trait ClearTempFilesTrait
{
    public function clearTempFiles(): void
    {
        $storage = Storage::get('thumbnail');
        $contents = $storage->listContents('/', true)->filter(fn (StorageAttributes $item) => $item->isDir() && preg_match('@(image|video|pdf)-thumb__[\d]+__'.preg_quote($this->getName(), '@').'(?:_auto_.+)?$@', $item->path()))->map(fn (StorageAttributes $attributes) => $attributes->path())->toArray();

        foreach ($contents as $item) {
            $storage->deleteDirectory($item);
        }
    }
}
