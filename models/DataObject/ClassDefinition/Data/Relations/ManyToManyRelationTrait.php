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

namespace OpenDxp\Model\DataObject\ClassDefinition\Data\Relations;

use OpenDxp\Db;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Fieldcollection\Data\AbstractData;
use OpenDxp\Model\DataObject\Localizedfield;
use OpenDxp\Model\Element;
use OpenDxp\Model\Element\DirtyIndicatorInterface;
use function is_array;

trait ManyToManyRelationTrait
{
    /**
     * Unless forceSave is set to true, this method will check if the field is dirty and skip the save if not
     */
    protected function skipSaveCheck(
        Localizedfield|AbstractData|\OpenDxp\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object,
        array $params = []): bool
    {
        $forceSave = $params['forceSave'] ?? false;

        if (
            $forceSave === false &&
            !DataObject::isDirtyDetectionDisabled()
        ) {
            if ($object instanceof DataObject\Localizedfield) {
                if ($object->getObject() instanceof DirtyIndicatorInterface && !$object->hasDirtyFields()) {
                    return true;
                }
            } elseif ($this->supportsDirtyDetection() && !$object->isFieldDirty($this->getName())) {
                return true;
            }
        }

        return false;
    }

    public function save(Localizedfield|AbstractData|\OpenDxp\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object, array $params = []): void
    {
        if ($this->skipSaveCheck($object, $params)) {
            return;
        }

        parent::save($object, $params);
    }

    protected function filterUnpublishedElements(mixed $data): array
    {
        if (!is_array($data)) {
            return [];
        }

        if (DataObject::doHideUnpublished()) {
            $publishedList = [];
            foreach ($data as $listElement) {
                if (Element\Service::isPublished($listElement)) {
                    $publishedList[] = $listElement;
                }
            }

            return $publishedList;
        }

        return $data;
    }

    /**
     * Filter by relation feature
     *
     *
     */
    public function getFilterConditionExt(mixed $value, string $operator, array $params = []): string
    {
        $prefix = '';
        $name = $params['name'] ?: $this->name;

        if ($params['brickPrefix']) {
            $prefix = $params['brickPrefix'];
            // The brick prefix might be quoted and with a dot suffix, if so, removing the first
            // and second last character to unquote
            $quoteIdentifierSymbol  = substr(Db::get()->quoteIdentifier(''), 0, 1);

            if (
                substr($prefix, 0, 1) === $quoteIdentifierSymbol &&
                substr($prefix, -2, 1) === $quoteIdentifierSymbol &&
                str_ends_with($prefix, '.')
            ) {
                // Case: `db`.
                $prefix = substr($prefix, 1, -2) . '.';
            } elseif (
                substr($prefix, 0, 1) === $quoteIdentifierSymbol &&
                substr($prefix, -1) === $quoteIdentifierSymbol
            ) {
                // Case: `db`
                $prefix = substr($prefix, 1, -1);
            }
        }

        return $this->getRelationFilterCondition($value, $operator, $prefix . $name);
    }
}
