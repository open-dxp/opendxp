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

namespace OpenDxp\Model\Translation;

use Doctrine\DBAL\ArrayParameterType;
use Exception;
use OpenDxp\Db\Helper;
use OpenDxp\Logger;
use OpenDxp\Model;
use OpenDxp\Model\User;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

/**
 * @internal
 *
 * @property \OpenDxp\Model\Translation $model
 */
class Dao extends Model\Dao\AbstractDao
{
    public const string TABLE_PREFIX = 'translations_';

    /**
     * @var array<string, true>
     */
    private static array $existingTables = [];

    public function getDatabaseTableName(): string
    {
        return self::TABLE_PREFIX . $this->model->getDomain();
    }

    /**
     * @throws NotFoundResourceException
     * @throws \Doctrine\DBAL\Exception
     */
    public function getByKey(string $key, ?array $languages = null): void
    {
        if (is_array($languages)) {
            $sql = sprintf(
                'SELECT * FROM %s WHERE `key` = :key AND `language` IN (:languages) ORDER BY `creationDate`',
                $this->getDatabaseTableName()
            );
        } else {
            $sql = sprintf(
                'SELECT * FROM %s WHERE `key` = :key ORDER BY `creationDate`',
                $this->getDatabaseTableName()
            );
        }

        $data = $this->db->fetchAllAssociative($sql,
            ['key' => $key, 'languages' => $languages],
            ['languages' => ArrayParameterType::STRING]
        );

        if ($data !== []) {
            foreach ($data as $d) {
                $this->model->addTranslation($d['language'], $d['text']);
                $this->model->setKey($d['key']);
                $this->model->setCreationDate($d['creationDate']);
                $this->model->setModificationDate($d['modificationDate']);
                $this->model->setType($d['type']);
                $this->model->setUserOwner($d['userOwner']);
                $this->model->setUserModification($d['userModification']);
            }
        } else {
            throw new NotFoundResourceException("Translation-Key -->'" . $key . "'<-- not found");
        }
    }

    /**
     * Save object to database
     */
    public function save(): void
    {
        if (!$this->tableExists($this->getDatabaseTableName())) {
            $this->createTable();
        }

        $this->updateModificationInfos();
        $sanitizer = $this->model->getTranslationSanitizer();

        $editableLanguages = [];
        if ($this->model->getDomain() != Model\Translation::DOMAIN_ADMIN && $user = User::getById($this->model->getUserModification())) {
            $editableLanguages = $user->getAllowedLanguagesForEditingWebsiteTranslations();
        }

        if ($this->model->getKey() !== '') {
            foreach ($this->model->getTranslations() as $language => $text) {
                if (count($editableLanguages) && !in_array($language, $editableLanguages)) {
                    Logger::warning(sprintf('User %s not allowed to edit %s translation', $user->getUsername(), $language));

                    continue;
                }

                if ($text != strip_tags($text)) {
                    $text = $sanitizer->sanitizeFor('body', $text);
                    $this->model->addTranslation($language, $text);
                }

                $data = [
                'key' => $this->model->getKey(),
                'type' => $this->model->getType(),
                'language' => $language,
                'text' => $text,
                'modificationDate' => $this->model->getModificationDate(),
                'creationDate' => $this->model->getCreationDate(),
                'userOwner' => $this->model->getUserOwner(),
                'userModification' => $this->model->getUserModification(),
                ];
                Helper::upsert($this->db, $this->getDatabaseTableName(), $data, $this->getPrimaryKey($this->getDatabaseTableName()));
            }
        }
    }

    /**
     * Deletes object from database
     */
    public function delete(): void
    {
        $this->db->delete($this->getDatabaseTableName(), [$this->db->quoteIdentifier('key') => $this->model->getKey()]);
    }

    /**
     * Returns a array containing all available languages
     */
    public function getAvailableLanguages(): array
    {
        $l = $this->db->fetchAllAssociative(
            sprintf('SELECT * FROM %s GROUP BY `language`', $this->getDatabaseTableName())
        );
        $languages = [];

        foreach ($l as $values) {
            $languages[] = $values['language'];
        }

        return $languages;
    }

    /**
     * Returns a array containing all available (registered) domains
     */
    public function getAvailableDomains(): array
    {
        $domainTables = $this->db->fetchAllAssociative("SHOW TABLES LIKE 'translations_%'");
        $domains = [];

        foreach ($domainTables as $domainTable) {
            $domain =  str_replace('translations_', '', $domainTable[array_key_first($domainTable)]);
            if ($this->isAValidDomain($domain)) {
                $domains[] = $domain;
            }
        }

        return $domains;
    }

    /**
     * Returns boolean, if the domain table exists & domain registered in config
     */
    public function isAValidDomain(string $domain): bool
    {
        return in_array($domain, $this->model->getRegisteredDomains(), true)
            && $this->tableExists(self::TABLE_PREFIX . $domain);
    }

    private function tableExists(string $table): bool
    {
        if (isset(self::$existingTables[$table])) {
            return true;
        }

        $exists = (bool) $this->db->fetchOne(
            'SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$table],
        );

        if ($exists) {
            self::$existingTables[$table] = true;
        }

        return $exists;
    }

    /**
     * @deprecated since OpenDXP 1.5 and will be removed in 2.0
     */
    public function createOrUpdateTable(): void
    {
        trigger_deprecation('open-dxp/opendxp', '1.5', 'Method "%s()" is deprecated and will be removed in 2.0.', __METHOD__);

        $this->createTable();
    }

    private function createTable(): void
    {
        $table = $this->getDatabaseTableName();

        if ($table === self::TABLE_PREFIX) {
            throw new Exception('Domain is missing to create new translation domain');
        }

        $this->db->executeQuery(sprintf(
            "CREATE TABLE IF NOT EXISTS `%s` (
                          `key` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
                          `type` varchar(10) DEFAULT NULL,
                          `language` varchar(10) NOT NULL DEFAULT '',
                          `text` text DEFAULT NULL,
                          `creationDate` int(11) unsigned DEFAULT NULL,
                          `modificationDate` int(11) unsigned DEFAULT NULL,
                          `userOwner` int(11) unsigned DEFAULT NULL,
                          `userModification` int(11) unsigned DEFAULT NULL,
                          PRIMARY KEY (`key`,`language`),
                          KEY `language` (`language`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            $table
        ));

        self::$existingTables[$table] = true;
    }

    protected function updateModificationInfos(): void
    {
        $updateTime = time();
        $this->model->setModificationDate($updateTime);

        if (!$this->model->getCreationDate()) {
            $this->model->setCreationDate($updateTime);
        }

        // auto assign user if possible, if no user present, use ID=0 which represents the "system" user
        $userId = \OpenDxp\Tool\Admin::getCurrentUser()?->getId() ?? 0;
        $this->model->setUserModification($userId);

        if ($this->model->getUserOwner() === null) {
            $this->model->setUserOwner($userId);
        }
    }
}
