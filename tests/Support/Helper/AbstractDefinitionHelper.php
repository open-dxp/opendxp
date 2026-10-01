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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Tests\Support\Helper;

use Codeception\Module;
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Tests\Support\Util\TestHelper;
use ReflectionClass;

/**
 * @deprecated since OpenDXP 1.5 and will be removed in 2.0
 */
abstract class AbstractDefinitionHelper extends Module
{
    protected array $config = [
        'initialize_definitions' => true,
        'cleanup' => true,
    ];

    protected function getClassManager(): Module|ClassManager
    {
        return $this->getModule('\\' . ClassManager::class);
    }

    public function _beforeSuite(array $settings = []): void
    {
        if ($this->config['initialize_definitions']) {
            if (TestHelper::supportsDbTests()) {
                $this->initializeDefinitions();
            } else {
                $this->debug(sprintf(
                    '[%s] Not initializing model definitions as DB is not connected',
                    strtoupper((new ReflectionClass($this))->getShortName())
                ));
            }
        }
    }

    public function _afterSuite(): void
    {
        if ($this->config['cleanup']) {
            TestHelper::cleanUp();
        }
    }

    public function createDataChild(string $type, ?string $name = null, bool $mandatory = false, bool $index = false, bool $visibleInGridView = true, bool $visibleInSearchResult = true): Data
    {
        if (!$name) {
            $name = $type;
        }

        $classname = 'OpenDxp\\Model\\DataObject\\ClassDefinition\\Data\\' . ucfirst($type);
        /** @var Data $child */
        $child = new $classname();
        $child->setName($name);
        $child->setTitle($name);
        $child->setMandatory($mandatory);
        $child->setIndex($index);
        $child->setVisibleGridView($visibleInGridView);
        $child->setVisibleSearch($visibleInSearchResult);

        return $child;
    }

    abstract public function initializeDefinitions(): void;
}
