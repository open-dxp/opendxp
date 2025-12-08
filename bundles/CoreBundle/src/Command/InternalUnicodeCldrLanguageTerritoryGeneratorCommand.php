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
use OpenDxp\Localization\LocaleServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'internal:unicode-cldr-language-territory-generator',
    description: 'For internal use only',
    hidden: true
)]
class InternalUnicodeCldrLanguageTerritoryGeneratorCommand extends AbstractCommand
{
    public function __construct(private readonly LocaleServiceInterface $localeService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $source = 'https://raw.githubusercontent.com/unicode-org/cldr/master/common/supplemental/supplementalData.xml';
        $data = file_get_contents($source);
        $xml = simplexml_load_string($data, null, LIBXML_NOCDATA);

        $languageRawData = [];

        foreach ($xml->territoryInfo->territory as $territory) {
            foreach ($territory->languagePopulation as $language) {
                $languageCode = (string) $language['type'];
                if ($this->localeService->isLocale($languageCode)) {
                    $populationAbsolute = $territory['population'] * $language['populationPercent'] / 100;

                    if (!isset($languageRawData[$languageCode])) {
                        $languageRawData[$languageCode] = [];
                    }

                    if ($this->localeService->isLocale($languageCode . '_' . $territory['type'])) {
                        $languageRawData[$languageCode][] = [
                            'country' => (string)$territory['type'],
                            'population' => $populationAbsolute,
                        ];
                    }
                }
            }
        }

        $finalData = [];

        foreach ($languageRawData as $languageCode => $rawLanguage) {
            usort($rawLanguage, fn ($a, $b) => $b['population'] <=> $a['population']);

            $finalData[$languageCode] = [];
            foreach ($rawLanguage as $territory) {
                $finalData[$languageCode][] = $territory['country'];
            }
        }

        $contents = to_php_data_file_format($finalData);
        $dataFile = OPENDXP_PATH . '/bundles/CoreBundle/public/misc/cldr-language-territory-mapping.php';
        File::putPhpFile($dataFile, $contents);

        $this->output->writeln('Updated mappings in ' . $dataFile);

        return 0;
    }
}
