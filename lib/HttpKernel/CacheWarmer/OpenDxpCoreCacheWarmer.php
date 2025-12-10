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

namespace OpenDxp\HttpKernel\CacheWarmer;

use OpenDxp\Bootstrap;
use OpenDxp\Helper\FileSystemHelper;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject;
use ReflectionClass;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * @internal
 */
class OpenDxpCoreCacheWarmer implements CacheWarmerInterface
{
    public function isOptional(): bool
    {
        return true;
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $classes = [];

        $this->libraryClasses($classes);
        $this->modelClasses($classes);
        $this->dataObjectClasses($classes);

        return $classes;
    }

    private function libraryClasses(array &$classes): void
    {
        $excludePattern = '@/lib/(Migrations|Maintenance|Sitemap|Workflow|Console|Composer|Translation/(Import|Export)|Image/Optimizer|DataObject/(GridColumnConfig|Import)|Test|Tool/Transliteration|(OpenDxp)\.php)@';

        $reflection = new ReflectionClass(Bootstrap::class);
        $dir = dirname($reflection->getFileName());

        $this->getClassesFromDirectory($dir, $excludePattern, 'OpenDxp', $classes);
    }

    private function modelClasses(array &$classes): void
    {
        $excludePattern = '@/models/(GridConfig|ImportConfig|Notification|Schedule|Tool/CustomReport|User|Workflow)@';

        $reflection = new ReflectionClass(Asset::class);
        $dir = dirname($reflection->getFileName());

        $this->getClassesFromDirectory($dir, $excludePattern, 'OpenDxp\Model', $classes);
    }

    private function getClassesFromDirectory(string $dir, string $excludePattern, string $NSPrefix, array &$classes): void
    {
        $files = FileSystemHelper::scanDirectory($dir);

        foreach ($files as $file) {
            $file = str_replace(DIRECTORY_SEPARATOR, '/', $file);
            if (is_file($file) && !preg_match($excludePattern, $file)) {
                $className = preg_replace('@^' . preg_quote($dir, '@') . '@', $NSPrefix, $file);
                $className = preg_replace('@\.php$@', '', $className);
                $className = str_replace(DIRECTORY_SEPARATOR, '\\', $className);

                // include classes, interfaces and traits
                // exclude invalid files like helper-functions
                if (!preg_match('/[_.-]/', $className)) {
                    $classes[] = $className;
                }
            }
        }
    }

    private function dataObjectClasses(array &$classes): void
    {
        $objectClassesFolder = OPENDXP_CLASS_DEFINITION_DIRECTORY;
        $files = glob($objectClassesFolder.'/*.php');

        foreach ($files as $file) {
            $className = DataObject::class . '\\' . preg_replace('/^definition_(.*)\.php$/', '$1', basename($file));
            $listingClass = $className . '\\Listing';

            $classes[] = $className;
            $classes[] = $listingClass;
        }

        $list = new DataObject\Objectbrick\Definition\Listing();
        $list = $list->loadNames();

        foreach ($list as $brickName) {
            $className = 'OpenDxp\\Model\\DataObject\\Objectbrick\\Data\\' . ucfirst($brickName);

            $classes[] = $className;
        }

        $list = new DataObject\Fieldcollection\Definition\Listing();
        $list = $list->loadNames();

        foreach ($list as $fcName) {
            $className = 'OpenDxp\\Model\\DataObject\\Fieldcollection\\Data\\' . ucfirst($fcName);

            $classes[] = $className;
        }
    }
}
