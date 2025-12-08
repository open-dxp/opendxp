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

use OpenDxp\Model\Element\Service;

class Document extends Data
{
    public function normalize(mixed $value, array $params = []): mixed
    {
        $element = $value;
        if (is_string($value)) {
            $element = Service::getElementByPath('document', $value);
        }
        if ($element instanceof \OpenDxp\Model\Document) {
            return $element->getId();
        }

        return null;
    }

    public function denormalize(mixed $value, array $params = []): mixed
    {
        if (is_numeric($value)) {
            return Service::getElementById('document', (int) $value);
        }
        return null;
    }

    #[\Override]
    public function transformGetterData(mixed $data, array $params = []): mixed
    {
        if (is_numeric($data)) {
            return \OpenDxp\Model\Document\Service::getElementById('document', (int) $data);
        }

        return $data;
    }

    #[\Override]
    public function transformSetterData(mixed $data, array $params = []): mixed
    {
        if ($data instanceof \OpenDxp\Model\Document) {
            return $data->getId();
        }

        return $data;
    }

    #[\Override]
    public function getDataFromEditMode(mixed $data, array $params = []): int|string|null
    {
        $element = $data;
        if (is_string($data)) {
            $element = Service::getElementByPath('document', $data);
        }
        if ($element instanceof \OpenDxp\Model\Document) {
            return $element->getId();
        }

        return '';
    }

    #[\Override]
    public function getDataForResource(mixed $data, array $params = []): mixed
    {
        if ($data instanceof \OpenDxp\Model\Document) {
            return $data->getId();
        }

        return $data;
    }

    #[\Override]
    public function getDataForEditMode(mixed $data, array $params = []): mixed
    {
        if (is_numeric($data)) {
            $data = Service::getElementById('document', (int) $data);
        }
        if ($data instanceof \OpenDxp\Model\Document) {
            return $data->getRealFullPath();
        }
        return '';
    }

    #[\Override]
    public function getDataForListfolderGrid(mixed $data, array $params = []): mixed
    {
        if (is_numeric($data)) {
            $data = \OpenDxp\Model\Document::getById((int) $data);
        }

        if ($data instanceof \OpenDxp\Model\Document) {
            return $data->getFullPath();
        }

        return $data;
    }

    #[\Override]
    public function resolveDependencies(mixed $data, array $params = []): array
    {
        if ($data instanceof \OpenDxp\Model\Document && isset($params['type'])) {
            $elementId = $data->getId();
            $elementType = $params['type'];

            $key = $elementType . '_' . $elementId;

            return [
                $key => [
                    'id' => $elementId,
                    'type' => $elementType,
                ], ];
        }

        return [];
    }

    #[\Override]
    public function getDataFromListfolderGrid(mixed $data, array $params = []): ?int
    {
        $data = \OpenDxp\Model\Document::getByPath($data);

        if ($data instanceof \OpenDxp\Model\Document) {
            return $data->getId();
        }

        return null;
    }
}
