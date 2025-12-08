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

namespace OpenDxp\Model\Asset\MetaData\ClassDefinition\Data;

use OpenDxp\Model\DataObject\Traits\SimpleNormalizerTrait;
use OpenDxp\Normalizer\NormalizerInterface;

abstract class Data implements DataDefinitionInterface, NormalizerInterface, \Stringable
{
    use SimpleNormalizerTrait;

    public function __toString(): string
    {
        return static::class;
    }

    public function transformGetterData(mixed $data, array $params = []): mixed
    {
        return $data;
    }

    public function transformSetterData(mixed $data, array $params = []): mixed
    {
        return $data;
    }

    public function getDataFromEditMode(mixed $data, array $params = []): mixed
    {
        return $data;
    }

    public function getDataForResource(mixed $data, array $params = []): mixed
    {
        return $data;
    }

    public function getDataFromResource(mixed $data, array $params = []): mixed
    {
        return $data;
    }

    public function getDataForEditMode(mixed $data, array $params = []): mixed
    {
        return $data;
    }

    public function isEmpty(mixed $data, array $params = []): bool
    {
        return empty($data);
    }

    public function checkValidity(mixed $data, array $params = []): void
    {
    }

    public function getDataForListfolderGrid(mixed $data, array $params = []): mixed
    {
        return $data;
    }

    public function getDataFromListfolderGrid(mixed $data, array $params = []): mixed
    {
        return $data;
    }

    public function resolveDependencies(mixed $data, array $params = []): array
    {
        return [];
    }

    public function getVersionPreview(mixed $value, array $params = []): string
    {
        return (string)$value;
    }

    public function getDataForSearchIndex(mixed $data, array $params = []): ?string
    {
        if (is_scalar($data)) {
            return $params['name'] . ':' . $data;
        }

        return null;
    }
}
