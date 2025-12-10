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

namespace OpenDxp\Bundle\CoreBundle\Command;

use OpenDxp\Console\AbstractCommand;
use OpenDxp\File;
use OpenDxp\Helper\ExportHelper;
use OpenDxp\Model\Asset;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * @internal
 */
#[AsCommand(
    name: 'internal:model-dao-mapping-generator',
    description: 'For internal use only',
    hidden: true
)]
class InternalModelDaoMappingGeneratorCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $finder = new Finder();
        $finder
            ->files()
            ->name('/(?<!Dao)\.php$/')
            ->in(OPENDXP_PATH . '/models');

        $map = [];

        foreach ($finder as $file) {
            $className = str_replace([DIRECTORY_SEPARATOR, '.php'], ['\\', ''], $file->getRelativePathname());
            $className = 'OpenDxp\\Model\\' . $className;

            if (class_exists($className)) {
                $parents = class_parents($className);
                if (is_array($parents) && in_array(\OpenDxp\Model\AbstractModel::class, $parents)) {
                    $reflection = new ReflectionClass($className);
                    if (!$reflection->isAbstract()) {
                        $daoClass = Asset::locateDaoClass($className);
                        if ($daoClass) {
                            $map[$className] = $daoClass;
                        }
                    }
                }
            }
        }

        ksort($map);

        $mapFile = realpath(__DIR__ . '/../../../../config/dao-classmap.php');
        File::putPhpFile($mapFile, ExportHelper::toPhpDataFileFormat($map));

        return 0;
    }
}
