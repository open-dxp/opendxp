<?php

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Model\Element\Tag;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Exception;
use OpenDxp\Db\Helper;
use OpenDxp\Model;
use OpenDxp\Model\Element\ElementType;
use OpenDxp\Model\Element\Tag;

/**
 * @internal
 *
 * @property \OpenDxp\Model\Element\Tag $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * @throws Model\Exception\NotFoundException
     */
    public function getById(int $id): void
    {
        $data = $this->db->fetchAssociative('SELECT * FROM tags WHERE id = ?', [$id]);
        if (!$data) {
            throw new Model\Exception\NotFoundException('Tag item with id ' . $id . ' not found');
        }
        $this->assignVariablesToModel($data);
    }

    /**
     * Save object to database
     *
     *
     * @throws Exception
     *
     * @todo: not all save methods return a boolean, why this one?
     */
    public function save(): bool
    {
        if (strlen(trim(strip_tags($this->model->getName()))) < 1) {
            throw new Exception(sprintf('Invalid name for Tag: %s', $this->model->getName()));
        }

        $this->db->beginTransaction();

        try {
            $dataAttributes = $this->model->getObjectVars();

            $originalIdPath = null;
            if ($this->model->getId()) {
                $originalIdPath = $this->db->fetchOne('SELECT idPath FROM tags WHERE id = ?', [$this->model->getId()]);
            }

            $data = [];
            foreach ($dataAttributes as $key => $value) {
                if (in_array($key, $this->getValidTableColumns('tags'))) {
                    $data[$key] = $value;
                }
            }

            Helper::upsert($this->db, 'tags', $data, $this->getPrimaryKey('tags'));

            $lastInsertId = $this->db->lastInsertId();
            if (!$this->model->getId() && $lastInsertId) {
                $this->model->setId((int) $lastInsertId);
            }

            //check for id-path and update it, if path has changed -> update all other tags that have idPath == idPath/id
            if ($originalIdPath && $originalIdPath != $this->model->getIdPath()) {
                $this->db->executeStatement(
                    'UPDATE tags SET idPath = REPLACE(idPath, ?, ?) WHERE idPath LIKE ?',
                    [$originalIdPath, $this->model->getIdPath(), Helper::escapeLike($originalIdPath) . $this->model->getId() . '/%']
                );
            }

            $this->db->commit();

            return true;
        } catch (Exception $e) {
            $this->db->rollBack();

            throw $e;
        }
    }

    /**
     * Deletes object from database
     *
     * @throws Exception
     */
    public function delete(): array
    {
        $this->db->beginTransaction();

        try {
            $toRemoveTagIds = $this->db->fetchFirstColumn(
                'SELECT id FROM tags WHERE idPath LIKE ?',
                [Helper::escapeLike($this->model->getIdPath()) . $this->model->getId() . '/%']
            );
            $toRemoveTagIds[] = $this->model->getId();

            $this->db->executeStatement('DELETE FROM tags_assignment WHERE tagid IN (?)', [$toRemoveTagIds], [ArrayParameterType::INTEGER]);
            $this->db->executeStatement('DELETE FROM tags WHERE id IN (?)', [$toRemoveTagIds], [ArrayParameterType::INTEGER]);
            $this->db->commit();

            return $toRemoveTagIds;
        } catch (Exception $e) {
            $this->db->rollBack();

            throw $e;
        }

    }

    /**
     * @return Model\Element\Tag[]
     */
    public function getTagsForElement(string $cType, int $cId): array
    {
        $tags = [];
        $tagIds = $this->db->fetchFirstColumn(
            'SELECT tagid FROM tags_assignment WHERE cid = ? AND ctype = ?',
            [$cId, $cType]
        );

        foreach ($tagIds as $tagId) {
            $tags[] = Model\Element\Tag::getById($tagId);
        }

        $tags = array_filter($tags);
        @usort($tags, fn ($left, $right) => strcmp($left->getNamePath(), $right->getNamePath()));

        return $tags;
    }

    public function addTagToElement(string $cType, int $cId): void
    {
        $this->doAddTagToElement($this->model->getId(), $cType, $cId);
    }

    protected function doAddTagToElement(int $tagId, string $cType, int $cId): void
    {
        $data = [
            'tagid' => $tagId,
            'ctype' => $cType,
            'cid' => $cId,
        ];
        Helper::upsert($this->db, 'tags_assignment', $data, $this->getPrimaryKey('tags_assignment'));
    }

    public function removeTagFromElement(string $cType, int $cId): void
    {
        $this->db->delete('tags_assignment', [
            'tagid' => $this->model->getId(),
            'ctype' => $cType,
            'cid' => $cId,
        ]);
    }

    /**
     * @throws Exception
     */
    public function setTagsForElement(string $cType, int $cId, array $tags): void
    {
        $this->db->beginTransaction();

        try {
            $this->db->delete('tags_assignment', ['ctype' => $cType, 'cid' => $cId]);

            foreach ($tags as $tag) {
                $this->doAddTagToElement($tag->getId(), $cType, $cId);
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();

            throw $e;
        }
    }

    public function batchAssignTagsToElement(string $cType, array $cIds, array $tagIds, bool $replace): void
    {
        if ($replace) {
            $this->db->executeStatement('DELETE FROM tags_assignment WHERE ctype = ? AND cid IN (?)', [$cType, $cIds], [ParameterType::STRING, ArrayParameterType::INTEGER]);
        }

        foreach ($tagIds as $tagId) {
            foreach ($cIds as $cId) {
                $this->doAddTagToElement($tagId, $cType, $cId);
            }
        }
    }

    /**
     * Retrieves all elements that have a specific tag or one of its child tags assigned
     *
     * @param Tag    $tag               The tag to search for
     * @param string $type              The type of elements to search for
     * @param array  $subtypes          Filter by subtypes, eg. page, object, email, folder etc.
     * @param array  $classNames        For objects only: filter by classnames
     * @param bool $considerChildTags Look for elements having one of $tag's children assigned
     *
     * @see ElementType
     */
    public function getElementsForTag(
        Tag $tag,
        string $type,
        array $subtypes = [],
        array $classNames = [],
        bool $considerChildTags = false
    ): array {
        $elements = [];

        $map = [
            ElementType::Document->value => ['documents', \OpenDxp\Model\Document::class],
            ElementType::Asset->value => ['assets', \OpenDxp\Model\Asset::class],
            ElementType::Object->value => ['objects', \OpenDxp\Model\DataObject\AbstractObject::class],
        ];

        $select = $this->db->createQueryBuilder()->select('*')
                           ->from('tags_assignment')
                           ->andWhere('tags_assignment.ctype = :ctype')->setParameter('ctype', $type);

        if ($considerChildTags) {
            $select->innerJoin('tags_assignment', 'tags', 'tags', 'tags.id = tags_assignment.tagid');
            $select->andWhere('tags_assignment.tagid = :considerTagId OR tags.idPath LIKE :considerTagPath')
                   ->setParameter('considerTagId', $tag->getId(), ParameterType::INTEGER)
                   ->setParameter('considerTagPath', Helper::escapeLike($tag->getFullIdPath()) . '%', ParameterType::STRING);
        } else {
            $select->andWhere('tags_assignment.tagid = :tagId')->setParameter('tagId', $tag->getId());
        }

        $select->innerJoin('tags_assignment', $map[$type][0], 'el', 'tags_assignment.cId = el.id');

        if ($subtypes !== []) {
            $select->andWhere($select->expr()->in('`type`', ':subtypes'))
                   ->setParameter('subtypes', $subtypes, ArrayParameterType::STRING);
        }

        if (ElementType::Object->value === $type && $classNames !== []) {
            $select->andWhere($select->expr()->in('className', ':classNames'))
                   ->setParameter('classNames', $classNames, ArrayParameterType::STRING);
        }

        $res = $select->executeQuery();

        while ($row = $res->fetchAssociative()) {
            $el = $map[$type][1]::getById($row['cid']);
            if ($el) {
                $elements[] = $el;
            }
        }

        return $elements;
    }

    /**
     * @param string $tagPath separated by "/"
     */
    public function getByPath(string $tagPath): ?Tag
    {
        $parentTagId = 0;

        $tag = null;
        $tagPath = ltrim($tagPath, '/');
        foreach (explode('/', $tagPath) as $tagItem) {
            $tags = new Tag\Listing();
            $tags->addConditionParam('name = ?', $tagItem);

            if (empty($parentTagId)) {
                $tags->addConditionParam('parentId = 0 OR parentId IS NULL'); // NULL is allowed by database schema
            } else {
                $tags->addConditionParam('parentId = ?', $parentTagId);
            }

            $tags->setLimit(1);

            $tags = $tags->load();

            if (count($tags) === 0) {
                return null;
            }

            $tag = $tags[0];
            $parentTagId = $tag->getId();
        }

        return $tag;
    }

    public function exists(): bool
    {
        if (is_null($this->model->getId())) {
            return false;
        }

        return (bool) $this->db->fetchOne('SELECT COUNT(*) FROM tags WHERE id = ?', [$this->model->getId()]);
    }
}
