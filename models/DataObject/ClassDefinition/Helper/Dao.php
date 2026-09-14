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

namespace OpenDxp\Model\DataObject\ClassDefinition\Helper;

use OpenDxp\Model\DataObject;

/**
 * @internal
 */
trait Dao
{
    protected function addIndexToField(DataObject\ClassDefinition\Data $field, string $table, string $columnTypeGetter = 'getColumnType', bool $considerUniqueIndex = false, bool $isLocalized = false, bool $isFieldcollection = false): void
    {
        $columnType = $field->$columnTypeGetter();

        $prefixes = [
            'p_index_' => ['enabled' => !$considerUniqueIndex && $field->getIndex(), 'unique' => false],
            'u_index_' => ['enabled' => $considerUniqueIndex && $field->getUnique(), 'unique' => true],

        ];

        foreach ($prefixes as $prefix => $config) {
            $enabled = $config['enabled'];
            $unique = $config['unique'];
            $uniqueStr = $unique ? ' UNIQUE ' : '';

            if ($enabled) {
                if (is_array($columnType)) {
                    // multicolumn field
                    foreach (array_keys($columnType) as $fkey) {
                        $indexName = $field->getName().'__'.$fkey;
                        $columns = $this->buildIndexColumnList($indexName, $unique, $isLocalized, $isFieldcollection);
                        if ($this->indexDoesNotExist($table, $prefix, $indexName)) {
                            $this->db->executeQuery(sprintf(
                                'ALTER TABLE %s ADD %sINDEX %s (%s);',
                                $this->db->quoteIdentifier($table),
                                $uniqueStr,
                                $this->db->quoteIdentifier($prefix . $indexName),
                                $columns
                            ));
                        }
                    }
                } else {
                    // single -column field
                    $indexName = $field->getName();
                    $columns = $this->buildIndexColumnList($indexName, $unique, $isLocalized, $isFieldcollection);
                    if ($this->indexDoesNotExist($table, $prefix, $indexName)) {
                        $this->db->executeQuery(sprintf(
                            'ALTER TABLE %s ADD %sINDEX %s (%s);',
                            $this->db->quoteIdentifier($table),
                            $uniqueStr,
                            $this->db->quoteIdentifier($prefix . $indexName),
                            $columns
                        ));
                    }
                }
            } elseif (is_array($columnType)) {
                // multicolumn field
                foreach (array_keys($columnType) as $fkey) {
                    $indexName = $field->getName().'__'.$fkey;
                    if ($this->indexExists($table, $prefix, $indexName)) {
                        $this->db->executeQuery(sprintf(
                            'ALTER TABLE %s DROP INDEX %s;',
                            $this->db->quoteIdentifier($table),
                            $this->db->quoteIdentifier($prefix . $indexName)
                        ));
                    }
                }
            } else {
                // single -column field
                $indexName = $field->getName();
                if ($this->indexExists($table, $prefix, $indexName)) {
                    $this->db->executeQuery(sprintf(
                        'ALTER TABLE %s DROP INDEX %s;',
                        $this->db->quoteIdentifier($table),
                        $this->db->quoteIdentifier($prefix . $indexName)
                    ));
                }
            }
        }
    }

    private function buildIndexColumnList(string $indexName, bool $unique, bool $isLocalized, bool $isFieldcollection): string
    {
        $columns = [$this->db->quoteIdentifier($indexName)];
        if ($unique) {
            if ($isLocalized) {
                $columns[] = $this->db->quoteIdentifier('language');
            } elseif ($isFieldcollection) {
                $columns[] = $this->db->quoteIdentifier('fieldname');
            }
        }

        return implode(',', $columns);
    }

    protected function addModifyColumn(string $table, string $colName, string $type, string $default, string $null): void
    {
        $existingColumns = $this->getValidTableColumns($table, false);

        $existingColName = null;

        // check for existing column case insensitive eg a rename from myInput to myinput
        $matchingExisting = preg_grep('/^' . preg_quote($colName, '/') . '$/i', $existingColumns);
        if (is_array($matchingExisting) && $matchingExisting !== []) {
            $existingColName = current($matchingExisting);
        }
        if ($existingColName === null) {
            $this->db->executeQuery(sprintf(
                'ALTER TABLE %s ADD COLUMN %s %s%s %s;',
                $this->db->quoteIdentifier($table),
                $this->db->quoteIdentifier($colName),
                $type,
                $default,
                $null
            ));
            $this->resetValidTableColumnsCache($table);
        } elseif (!DataObject\ClassDefinition\Service::skipColumn($this->tableDefinitions, $table, $colName, $type, $default, $null)) {
            $this->db->executeQuery(sprintf(
                'ALTER TABLE %s CHANGE COLUMN %s %s %s%s %s;',
                $this->db->quoteIdentifier($table),
                $this->db->quoteIdentifier($existingColName),
                $this->db->quoteIdentifier($colName),
                $type,
                $default,
                $null
            ));
        }
    }

    /**
     * @param string[] $columnsToRemove
     * @param string[] $protectedColumns
     */
    protected function removeUnusedColumns(string $table, array $columnsToRemove, array $protectedColumns): void
    {
        $dropColumns = [];
        foreach ($columnsToRemove as $value) {
            //if (!in_array($value, $protectedColumns)) {
            if (!in_array(strtolower($value), array_map(strtolower(...), $protectedColumns))) {
                $dropColumns[] = 'DROP COLUMN ' . $this->db->quoteIdentifier($value);
                $this->removeIndices($table, [$value], []);
            }
        }
        if ($dropColumns) {
            $this->db->executeQuery(sprintf('ALTER TABLE %s %s;', $this->db->quoteIdentifier($table), implode(', ', $dropColumns)));
            $this->resetValidTableColumnsCache($table);
        }
    }

    /**
     * @param string[] $tables
     */
    protected function handleEncryption(DataObject\ClassDefinition $classDefinition, array $tables): void
    {
        if ($classDefinition->getEncryption()) {
            $this->encryptTables($tables);
            $classDefinition->addEncryptedTables($tables);
        } elseif ($classDefinition->hasEncryptedTables()) {
            $this->decryptTables($classDefinition, $tables);
            $classDefinition->removeEncryptedTables($tables);
        }
    }

    /**
     * @param string[] $tables
     */
    protected function encryptTables(array $tables): void
    {
        foreach ($tables as $table) {
            $this->db->executeQuery('ALTER TABLE ' . $this->db->quoteIdentifier($table) . ' ENCRYPTED=YES;');
        }
    }

    /**
     * @param string[] $tables
     */
    protected function decryptTables(DataObject\ClassDefinition $classDefinition, array $tables): void
    {
        foreach ($tables as $table) {
            if ($classDefinition->isEncryptedTable($table)) {
                $this->db->executeQuery('ALTER TABLE ' . $this->db->quoteIdentifier($table) . ' ENCRYPTED=NO;');
            }
        }
    }

    /**
     * @param string[] $columnsToRemove
     * @param string[] $protectedColumns
     */
    protected function removeIndices(string $table, array $columnsToRemove, array $protectedColumns): void
    {
        if ($columnsToRemove) {
            $lowerCaseColumns = array_map(strtolower(...), $protectedColumns);
            foreach ($columnsToRemove as $value) {
                if (!in_array(strtolower($value), $lowerCaseColumns) && $this->indexExists($table, 'u_index_', $value)) {
                    $this->db->executeQuery(sprintf(
                        'ALTER TABLE %s DROP INDEX %s;',
                        $this->db->quoteIdentifier($table),
                        $this->db->quoteIdentifier('u_index_' . $value)
                    ));
                }
            }
            $this->resetValidTableColumnsCache($table);
        }
    }

    /**
     * For MariaDB, it would be possible to use 'ADD/DROP INDEX IF EXISTS' but this is not supported by MySQL
     */
    protected function indexExists(string $table, string $prefix, string $indexName): bool
    {
        $exist = $this->db->fetchFirstColumn(
            'SELECT COUNT(*)
            FROM information_schema.statistics
            WHERE table_name = ?
                AND index_name = ?
                AND table_schema = DATABASE();',
            [
                $table,
                $prefix . $indexName,
            ]
        );

        return (count($exist) > 0) && ($exist[0] > 0);
    }

    protected function indexDoesNotExist(string $table, string $prefix, string $indexName): bool
    {
        return !$this->indexExists($table, $prefix, $indexName);
    }
}
