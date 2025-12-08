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

namespace OpenDxp\Model\DataObject\Fieldcollection\Definition;

use OpenDxp\Model\DataObject\Fieldcollection\Definition;

class Listing
{
    /**
     * @return Definition[]
     */
    public function load(): array
    {
        $fields = [];

        $files = $this->loadFileNames();
        foreach ($files as $file) {
            $fields[] = include $file;
        }

        return $fields;
    }

    /**
     * @return string[]
     */
    public function loadNames(): array
    {
        $fields = [];

        $files = $this->loadFileNames();
        foreach ($files as $file) {
            $fields[] = basename($file, '.php');
        }

        return $fields;
    }

    /**
     * @return string[]
     */
    public function loadFileNames(): array
    {
        $filenames = [];

        $fieldCollectionFolders = array_filter(array_unique(array_map(realpath(...), [
            OPENDXP_CLASS_DEFINITION_DIRECTORY . '/fieldcollections',
            OPENDXP_CUSTOM_CONFIGURATION_CLASS_DEFINITION_DIRECTORY . '/fieldcollections',
        ])));

        foreach ($fieldCollectionFolders as $fieldCollectionFolder) {
            $files = glob($fieldCollectionFolder . '/*.php');
            foreach ($files as $file) {
                $realFile = realpath($file);
                if ($realFile) {
                    $filenames[] = $realFile;
                }
            }
        }

        return array_unique($filenames);
    }
}
