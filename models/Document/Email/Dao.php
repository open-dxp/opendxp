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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Model\Document\Email;

use Exception;
use OpenDxp\Model;
use Override;

/**
 * @internal
 *
 * @property \OpenDxp\Model\Document\Email $model
 */
class Dao extends Model\Document\PageSnippet\Dao
{
    /**
     * Get the data for the object by the given id, or by the id which is set in the object
     *
     *
     * @throws Model\Exception\NotFoundException
     */
    #[Override]
    public function getById(?int $id = null): void
    {
        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchAssociative("SELECT documents.*, documents_email.*, tree_locks.locked FROM documents
            LEFT JOIN documents_email ON documents.id = documents_email.id
            LEFT JOIN tree_locks ON documents.id = tree_locks.id AND tree_locks.type = 'document'
                WHERE documents.id = ?", [$this->model->getId()]);

        if ($data) {
            $data['published'] = (bool)$data['published'];
            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException('Email Document with the ID ' . $this->model->getId() . " doesn't exists");
        }
    }

    #[Override]
    public function create(): void
    {
        parent::create();

        $this->db->insert('documents_email', [
            'id' => $this->model->getId(),
        ]);
    }

    /**
     * @throws Exception
     */
    #[Override]
    public function delete(): void
    {
        $this->deleteAllProperties();

        parent::delete();
    }
}
