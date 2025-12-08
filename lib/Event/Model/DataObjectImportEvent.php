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

namespace OpenDxp\Event\Model;

use OpenDxp\Model\DataObject\Concrete;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class DataObjectImportEvent
 *
 * @package OpenDxp\Event\Model
 */
class DataObjectImportEvent extends Event
{
    protected Concrete $object;

    protected mixed $rowData = null;

    protected mixed $additionalData = null;

    protected mixed $context = null;

    /**
     * DataObjectImportEvent constructor.
     *
     */
    public function __construct(protected mixed $config, protected string $originalFile)
    {
    }

    public function getConfig(): mixed
    {
        return $this->config;
    }

    public function setConfig(mixed $config): void
    {
        $this->config = $config;
    }

    public function getOriginalFile(): string
    {
        return $this->originalFile;
    }

    public function setOriginalFile(string $originalFile): void
    {
        $this->originalFile = $originalFile;
    }

    public function getObject(): Concrete
    {
        return $this->object;
    }

    public function setObject(Concrete $object): void
    {
        $this->object = $object;
    }

    public function getRowData(): mixed
    {
        return $this->rowData;
    }

    public function setRowData(mixed $rowData): void
    {
        $this->rowData = $rowData;
    }

    public function getAdditionalData(): mixed
    {
        return $this->additionalData;
    }

    public function setAdditionalData(mixed $additionalData): void
    {
        $this->additionalData = $additionalData;
    }

    public function getContext(): mixed
    {
        return $this->context;
    }

    public function setContext(mixed $context): void
    {
        $this->context = $context;
    }
}
