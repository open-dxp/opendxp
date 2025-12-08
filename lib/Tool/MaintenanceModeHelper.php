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

namespace OpenDxp\Tool;

use Doctrine\DBAL\Connection;
use Exception;
use InvalidArgumentException;
use OpenDxp;
use OpenDxp\Event\SystemEvents;
use OpenDxp\Model\Tool\TmpStore;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\RequestStack;

class MaintenanceModeHelper implements MaintenanceModeHelperInterface
{
    protected const ENTRY_ID = 'maintenance_mode';

    public function __construct(protected RequestStack $requestStack, protected Connection $db)
    {
    }

    public function activate(string $sessionId): void
    {
        if (empty($sessionId)) {
            $sessionId = $this->requestStack->getSession()->getId();
        }

        if (empty($sessionId)) {
            throw new InvalidArgumentException('Pass sessionId to activate the maintenance mode');
        }

        $this->addEntry($sessionId);

        OpenDxp::getEventDispatcher()->dispatch(new GenericEvent(), SystemEvents::MAINTENANCE_MODE_ACTIVATE);
    }

    public function deactivate(): void
    {
        $this->removeEntry();

        OpenDxp::getEventDispatcher()->dispatch(new GenericEvent(), SystemEvents::MAINTENANCE_MODE_DEACTIVATE);
    }

    public function isActive(?string $matchSessionId = null): bool
    {
        try {
            if (!$this->db->isConnected()) {
                $this->db->connect();
            }
        } catch (Exception) {
            return false;
        }

        if (!$maintenanceModeEntry = $this->getEntry()) {
            return false;
        }

        return $matchSessionId === null || $matchSessionId !== $maintenanceModeEntry;
    }

    protected function addEntry(string $sessionId): void
    {
        TmpStore::add(self::ENTRY_ID, $sessionId);
    }

    protected function getEntry(): ?string
    {
        try {
            $tmpStore = TmpStore::get(self::ENTRY_ID);
        } catch (Exception) {
            //nothing to log as the tmp doesn't exist
            return null;
        }

        return $tmpStore instanceof TmpStore ? $tmpStore->getData() : null;
    }

    protected function removeEntry(): void
    {
        try {
            TmpStore::delete(self::ENTRY_ID);
        } catch (Exception) {
            //nothing to log as the tmp doesn't exist
        }
    }
}
