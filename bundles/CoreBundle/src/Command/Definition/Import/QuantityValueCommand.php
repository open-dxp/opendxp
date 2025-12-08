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

namespace OpenDxp\Bundle\CoreBundle\Command\Definition\Import;

use InvalidArgumentException;
use OpenDxp\Console\AbstractCommand;
use OpenDxp\Console\Traits\DryRun;
use OpenDxp\Model\DataObject\QuantityValue\Service;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'opendxp:definition:import:units',
    description: 'Import quantity value units from a JSON export',
    aliases: ['definition:import:units']
)]
class QuantityValueCommand extends AbstractCommand
{
    use DryRun;

    public function __construct(private Service $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'Path to quantity value unit JSON export file')
            ->addOption(
                'override',
                'o',
                InputOption::VALUE_NEGATABLE,
                'Override the existing unit definition'
            );

        $this->configureDryRunOption();
    }

    /**
     * Validate and return path to JSON file
     *
     */
    private function getPath(): string
    {
        $path = $this->input->getArgument('path');
        if (!file_exists($path) || !is_readable($path)) {
            throw new InvalidArgumentException('File does not exist');
        }

        return $path;
    }

    /**
     * Load JSON data from file
     */
    private function getJson(string $path): string
    {
        $content = file_get_contents($path);

        // try to decode json here as we want to fail early if file is no valid JSON
        $json = json_decode($content);
        if (null === $json) {
            throw new InvalidArgumentException('JSON could not be decoded');
        }

        return $content;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $this->getPath();
        $json = $this->getJson($path);
        $override = $this->input->getOption('override') ?? false;
        $result = false;
        if ($this->isDryRun()) {
            $this->output->writeln($this->prefixDryRun(sprintf('Skipping the unit definition import from %s', $path)));
            $result = true;
        } else {
            $this->output->writeln(sprintf('Importing quantity value unit definitions from %s', $path));
            $result = $this->service->importDefinitionFromJson($json, $override);
        }

        if ($result) {
            $this->output->writeln('Successfully imported definitions');

            return 0;
        }
        $this->output->writeln('<error>ERROR:</error> Failed to import definitions');

        return 1;
    }
}
