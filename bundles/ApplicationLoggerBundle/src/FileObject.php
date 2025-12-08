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

namespace OpenDxp\Bundle\ApplicationLoggerBundle;

use const OPENDXP_PROJECT_ROOT;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToWriteFile;
use OpenDxp\Logger;
use OpenDxp\Tool\Storage;

final class FileObject implements \Stringable
{
    public function __construct(protected string $data, protected ?string $filename = null)
    {
        if (!$this->filename) {
            $this->filename = date('/Y/m/d/') . uniqid('fileobject_', true);
        }
        $storage = Storage::get('application_log');

        try {
            $storage->write($this->filename, $this->data);
        } catch (FilesystemException | UnableToWriteFile) {
            Logger::warn('Application Logger could not write File Object:'.$this->filename);
        }
    }

    public function getSystemPath(): ?string
    {
        return $this->filename;
    }

    public function getFilename(): string
    {
        return preg_replace('/^'.preg_quote(OPENDXP_PROJECT_ROOT, '/').'/', '', $this->filename);
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function __toString(): string
    {
        return $this->getFilename();
    }
}
