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

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use Codeception\Lib\ModuleContainer;
use OpenDxp\Bundle\GlossaryBundle\Installer;
use OpenDxp\Bundle\GlossaryBundle\Model\Glossary;
use OpenDxp\Tests\Support\Util\Autoloader;

/**
 * @deprecated since OpenDXP 1.5 and will be removed in 2.0
 */
class Unit extends \Codeception\Module
{
    public function __construct(ModuleContainer $moduleContainer, ?array $config = null)
    {
        $this->config = array_merge($this->config, [
            'run_installer' => true,
        ]);

        parent::__construct($moduleContainer, $config);
    }

    public function _beforeSuite(array $settings = []): void
    {
        $this->installOpenDxpGlossaryBundle();
    }

    private function installOpenDxpGlossaryBundle(): void
    {
        if ($this->config['run_installer']) {
            /** @var \OpenDxp $openDxpModule */
            $openDxpModule = $this->getModule('\\' . OpenDxp::class);

            $this->debug('[OpenDxpGlossaryBundle] Running OpenDxpGlossaryBundle installer');

            $installer = $openDxpModule->getContainer()->get(Installer::class);
            $installer->install();

            //explicitly load installed classes so that the new ones are used during tests
            Autoloader::load(Glossary::class);
        }
    }
}
