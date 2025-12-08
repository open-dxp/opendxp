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

namespace OpenDxp\DataObject\BlockDataMarshaller;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Exception;
use OpenDxp;
use OpenDxp\Element\MarshallerService;
use OpenDxp\Logger;
use OpenDxp\Marshaller\MarshallerInterface;
use OpenDxp\Model\DataObject\ClassDefinition\Data\AfterDecryptionUnmarshallerInterface;
use OpenDxp\Model\DataObject\ClassDefinition\Data\BeforeEncryptionMarshallerInterface;

/**
 * @internal
 */
class EncryptedField implements MarshallerInterface
{
    /**
     * EncryptedField constructor.
     *
     */
    public function __construct(protected MarshallerService $marshallerService)
    {
    }

    public function marshal(mixed $value, array $params = []): mixed
    {
        if ($value !== null) {
            $fd = $params['fieldDefinition'];
            $delegateFd = $fd->getDelegate();

            if ($this->marshallerService->supportsFielddefinition('block', $delegateFd->getFieldtype())) {
                $marshaller = $this->marshallerService->buildFieldefinitionMarshaller('block', $delegateFd->getFieldtype());
                $value = $marshaller->marshal($value, ['fieldDefinition' => $delegateFd, 'format' => 'block']);
            }

            return $this->encrypt($value, $params);
        }

        return null;
    }

    public function unmarshal(mixed $value, array $params = []): mixed
    {
        if ($value !== null) {
            $fd = $params['fieldDefinition'];
            $delegateFd = $fd->getDelegate();

            $decryptedValue = $this->decrypt($value, $params);

            if ($this->marshallerService->supportsFielddefinition('block', $delegateFd->getFieldtype())) {
                $marshaller = $this->marshallerService->buildFieldefinitionMarshaller('block', $delegateFd->getFieldtype());

                $decryptedValue = $marshaller->unmarshal($decryptedValue, ['fieldDefinition' => $delegateFd, 'format' => 'block']);
            }

            return $decryptedValue;
        }

        return null;
    }

    /**
     *
     *
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public function encrypt(mixed $data, array $params = []): string
    {
        if (!is_null($data)) {
            $object = $params['object'] ?? null;

            $key = OpenDxp::getContainer()->getParameter('opendxp.encryption.secret');

            try {
                $key = Key::loadFromAsciiSafeString($key);
            } catch (Exception) {
                throw new Exception('could not load key');
            }
            // store it in raw binary mode to preserve space

            $fd = $params['fieldDefinition'];
            $delegateFd = $fd->getDelegate();

            if ($delegateFd instanceof BeforeEncryptionMarshallerInterface) {
                $data = $delegateFd->marshalBeforeEncryption($data, $object, $params);
            }

            $data = Crypto::encrypt((string)$data, $key);
        }

        return $data;
    }

    /**
     *
     *
     * @throws Exception
     */
    public function decrypt(?string $data, array $params = []): ?string
    {
        if ($data) {
            $object = $params['object'] ?? null;
            $fd = $params['fieldDefinition'];
            $delegateFd = $fd->getDelegate();

            try {
                $key = OpenDxp::getContainer()->getParameter('opendxp.encryption.secret');

                try {
                    $key = Key::loadFromAsciiSafeString($key);
                } catch (Exception $e) {
                    throw new Exception('could not load key', $e->getCode(), $e);
                }

                if (!(isset($params['skipDecryption']) && $params['skipDecryption'])) {
                    $data = Crypto::decrypt($data, $key);
                }

                if ($delegateFd instanceof AfterDecryptionUnmarshallerInterface) {
                    return $delegateFd->unmarshalAfterDecryption($data, $object, $params);
                }

                return $data;
            } catch (Exception $e) {
                Logger::error((string) $e);

                throw new Exception('encrypted field ' . $delegateFd->getName() . ' cannot be decoded', $e->getCode(), $e);
            }
        }

        return null;
    }
}
