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

namespace OpenDxp\Model\Notification;

use Doctrine\DBAL\Exception;
use OpenDxp\DateFormat;
use OpenDxp\Db\Helper;
use OpenDxp\Model\Dao\AbstractDao;
use OpenDxp\Model\Element\Service;
use OpenDxp\Model\Exception\NotFoundException;
use OpenDxp\Model\Notification;
use OpenDxp\Model\User;
use UnexpectedValueException;

/**
 * @internal
 *
 * @property Notification $model
 */
class Dao extends AbstractDao
{
    public const string DB_TABLE_NAME = 'notifications';

    /**
     * @throws NotFoundException
     * @throws Exception
     */
    public function getById(int $id): void
    {
        $sql = sprintf('SELECT * FROM `%s` WHERE id = ?', self::DB_TABLE_NAME);
        $data = $this->db->fetchAssociative($sql, [$id]);

        if ($data === false) {
            $message = sprintf('Notification with id %d not found', $id);

            throw new NotFoundException($message);
        }

        $this->assignVariablesToModel($data);
    }

    /**
     * Save notification
     *
     * @throws Exception
     */
    public function save(): void
    {
        $this->model->setModificationDate(date(DateFormat::DATETIME));

        if ($this->model->getId() === null || !$this->model->getCreationDate()) {
            $this->model->setCreationDate($this->model->getModificationDate());
        }

        Helper::upsert(
            $this->db,
            self::DB_TABLE_NAME,
            $this->getData($this->model),
            $this->getPrimaryKey(self::DB_TABLE_NAME)
        );

        if ($this->model->getId() === null) {
            $this->model->setId((int) $this->db->lastInsertId());
        }
    }

    /**
     * Delete notification
     *
     * @throws Exception
     */
    public function delete(): void
    {
        $this->db->delete(self::DB_TABLE_NAME, [
            'id' => $this->model->getId(),
        ]);
    }

    protected function assignVariablesToModel(array $data): void
    {
        $sender = null;

        if ($data['sender']) {
            $user = User::getById($data['sender']);
            if ($user instanceof User) {
                $sender = $user;
            }
        }

        $recipient = null;

        if ($data['recipient']) {
            $user = User::getById($data['recipient']);
            if ($user instanceof User) {
                $recipient = $user;
            }
        }

        if (!$recipient instanceof User) {
            throw new UnexpectedValueException(sprintf('No user found with the ID %d', $data['recipient']));
        }

        if (!isset($data['title']) || !is_string($data['title']) || $data['title'] === '') {
            throw new UnexpectedValueException('Title of the Notification cannot be empty');
        }

        if (!isset($data['message']) || !is_string($data['message']) || $data['message'] === '') {
            throw new UnexpectedValueException('Message text of the Notification cannot be empty');
        }

        $linkedElement = null;

        if ($data['linkedElement']) {
            $linkedElement = Service::getElementById($data['linkedElementType'], (int) $data['linkedElement']);
        }

        $this->model->setId((int)$data['id']);
        $this->model->setCreationDate($data['creationDate'] ?? $data['modificationDate']);
        $this->model->setModificationDate($data['modificationDate']);
        $this->model->setSender($sender);
        $this->model->setRecipient($recipient);
        $this->model->setTitle($data['title']);
        $this->model->setType($data['type'] ?? 'info');
        $this->model->setMessage($data['message']);
        $this->model->setLinkedElement($linkedElement);
        $this->model->setRead($data['read'] === 1);
        $this->model->setPayload($data['payload']);
    }

    protected function getData(Notification $model): array
    {
        return [
            'id' => $model->getId(),
            'creationDate' => $model->getCreationDate(),
            'type' => $model->getType() ?? 'info',
            'modificationDate' => $model->getModificationDate(),
            'sender' => $model->getSender()?->getId(),
            'recipient' => $model->getRecipient()?->getId(),
            'title' => $model->getTitle(),
            'message' => $model->getMessage(),
            'linkedElement' => $model->getLinkedElement()?->getId(),
            'linkedElementType' => $model->getLinkedElementType(),
            'read' => (int) $model->isRead(),
            'payload' => $model->getPayload(),
        ];
    }
}
