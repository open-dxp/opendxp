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

namespace OpenDxp\Bundle\CoreBundle\Command\Asset;

use Exception;
use OpenDxp;
use OpenDxp\Console\AbstractCommand;
use OpenDxp\Db\Helper;
use OpenDxp\Model\Asset;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'opendxp:assets:generate-checksums',
    description: 'Re-generates checksum for specific or all assets',
)]
class GenerateChecksumCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addOption(
                'id',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'only generate checksum for assets with this (IDs)'
            )
            ->addOption(
                'parent',
                'p',
                InputOption::VALUE_OPTIONAL,
                'only generate checksum for assets in this folder (ID)'
            )
            ->addOption(
                'missing-only',
                'm',
                InputOption::VALUE_NONE,
                'only generate checksum for assets which have no checksum yet'
            )
            ->addOption(
                'path-pattern',
                null,
                InputOption::VALUE_OPTIONAL,
                'only generate checksum for the given regex pattern (path + filename), example:  ^/Sample.*urban.jpg$'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $conditionVariables = [];
        $missingOnly = $input->getOption('missing-only');

        $conditions = [];

        if ($input->getOption('parent')) {
            $parent = Asset::getById((int) $input->getOption('parent'));
            if ($parent instanceof Asset\Folder) {
                $conditions[] = "path LIKE '" . Helper::escapeLike($parent->getRealFullPath()) . "/%'";
            } else {
                $this->writeError($input->getOption('parent') . ' is not a valid asset folder ID!');
                exit;
            }
        }

        if ($ids = $input->getOption('id')) {
            $conditions[] = sprintf('id in (%s)', implode(',', $ids));
        }

        if ($regex = $input->getOption('path-pattern')) {
            $conditions[] = 'CONCAT(`path`, filename) REGEXP ?';
            $conditionVariables[] = $regex;
        }

        $list = new Asset\Listing();
        $list->setCondition(implode(' AND ', $conditions), $conditionVariables);
        $total = $list->getTotalCount();
        $perLoop = 10;

        for ($i = 0; $i < (ceil($total / $perLoop)); $i++) {
            $list->setLimit($perLoop);
            $list->setOffset($i * $perLoop);
            $assets = $list->load();
            foreach ($assets as $asset) {
                try {
                    if ($missingOnly && $asset->getCustomSetting('checksum')) {
                        continue;
                    }

                    if ($asset->getType() === 'folder') {
                        continue;
                    }

                    $this->output->writeln(' generating checksum for asset: ' . $asset->getRealFullPath() . ' | ' . $asset->getId());
                    $asset->generateChecksum();
                } catch (Exception) {
                    $this->output->writeln(' error generating checksum for asset: ' . $asset->getRealFullPath() . ' | ' . $asset->getId());
                }
            }

            OpenDxp::collectGarbage();
        }

        return 0;
    }
}
