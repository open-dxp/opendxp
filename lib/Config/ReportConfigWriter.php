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

namespace OpenDxp\Config;

use Exception;
use OpenDxp\Event\Report\SettingsEvent;
use OpenDxp\Event\ReportEvents;
use OpenDxp\Model\Tool\SettingsStore;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Handles writing/merging report config and emitting an event on config save.
 *
 * @internal
 */
final readonly class ReportConfigWriter
{
    const string REPORT_SETTING_ID = 'reports';

    const string REPORT_SETTING_SCOPE = 'opendxp';

    public function __construct(private EventDispatcherInterface $eventDispatcher)
    {
    }

    /**
     * @throws Exception
     */
    public function write(array $settings): void
    {
        $settingsEvent = new SettingsEvent($settings);
        $this->eventDispatcher->dispatch(
            $settingsEvent,
            ReportEvents::SAVE_SETTINGS
        );

        $settings = $settingsEvent->getSettings();

        SettingsStore::set(
            self::REPORT_SETTING_ID,
            json_encode($settings),
            SettingsStore::TYPE_STRING,
            self::REPORT_SETTING_SCOPE
        );
    }

    public function mergeConfig(array $values): void
    {
        // the config returned from getReportConfig is readonly
        // so we create a new writable one here
        $config = \OpenDxp\Config::getReportConfig();
        $config = [...$config, ...$values];

        $this->write($config);
    }
}
