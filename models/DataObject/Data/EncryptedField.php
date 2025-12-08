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

namespace OpenDxp\Model\DataObject\Data;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Exception;
use OpenDxp;
use OpenDxp\Logger;
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Model\DataObject\OwnerAwareFieldInterface;
use OpenDxp\Model\DataObject\Traits\OwnerAwareFieldTrait;
use OpenDxp\Tool\Serialize;

class EncryptedField implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;

    protected mixed $encrypted = null;

    public function __construct(protected Data $delegate, protected mixed $plain)
    {
        $this->markMeDirty();
    }

    public function getDelegate(): Data
    {
        return $this->delegate;
    }

    public function setDelegate(Data $delegate): void
    {
        $this->delegate = $delegate;
    }

    public function getPlain(): mixed
    {
        return $this->plain;
    }

    public function setPlain(mixed $plain): void
    {
        $this->plain = $plain;
        $this->markMeDirty();
    }

    /**
     *
     * @throws Exception
     */
    public function __sleep(): array
    {
        if ($this->plain) {
            try {
                $key = OpenDxp::getContainer()->getParameter('opendxp.encryption.secret');
                $key = Key::loadFromAsciiSafeString($key);
                $data = $this->plain;
                //clear owner to avoid recursion
                if ($data instanceof OwnerAwareFieldInterface) {
                    $data->_setOwner(null);
                    $data->_setOwnerFieldname('');
                }
                $data = Serialize::serialize($data);

                $data = Crypto::encrypt($data, $key, true);
                $this->encrypted = $data;
            } catch (Exception $e) {
                Logger::error((string) $e);

                throw new Exception('could not load key', $e->getCode(), $e);
            }

            return ['encrypted', '_owner'];
        }

        return [];
    }

    /**
     * @throws Exception
     */
    public function __wakeup(): void
    {
        if ($this->encrypted) {
            try {
                $key = OpenDxp::getContainer()->getParameter('opendxp.encryption.secret');
                $key = Key::loadFromAsciiSafeString($key);

                $data = Crypto::decrypt($this->encrypted, $key, true);

                $data = Serialize::unserialize($data);

                if ($data instanceof OwnerAwareFieldInterface) {
                    $data->_setOwner($this->_owner);
                    $data->_setOwnerFieldname('_owner');
                }

                $this->plain = $data;
            } catch (Exception $e) {
                Logger::error((string) $e);

                throw new Exception('could not load key', $e->getCode(), $e);
            }
        }

        $this->encrypted = null;
    }
}
