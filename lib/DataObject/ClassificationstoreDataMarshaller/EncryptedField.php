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

namespace OpenDxp\DataObject\ClassificationstoreDataMarshaller;

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
     * Localizedfields constructor.
     *
     */
    public function __construct(protected MarshallerService $marshallerService)
    {
    }

    public function marshal(mixed $value, array $params = []): mixed
    {
        if ($value !== null) {
            $encryptedValue = null;
            $encryptedValue2 = null;

            if (is_array($value)) {
                /** @var \OpenDxp\Model\DataObject\ClassDefinition\Data\EncryptedField $fd */
                $fd = $params['fieldDefinition'];
                $delegateFd = $fd->getDelegate();

                if ($this->marshallerService->supportsFielddefinition('classificationstore', $delegateFd->getFieldtype())) {
                    $marshaller = $this->marshallerService->buildFieldefinitionMarshaller('classificationstore', $delegateFd->getFieldtype());
                    $encodedData = $marshaller->marshal($value, ['fieldDefinition' => $delegateFd, 'format' => 'classificationstore']);

                    if (is_array($encodedData)) {
                        $encryptedValue = $this->encrypt($encodedData['value'], $params);
                        $encryptedValue2 = $this->encrypt($encodedData['value2'], $params);
                    }
                }
            } else {
                $encryptedValue = $this->encrypt($value, $params);
            }

            return [
                'value' => $encryptedValue,
                'value2' => $encryptedValue2,
            ];
        }

        return null;
    }

    public function unmarshal(mixed $value, array $params = []): mixed
    {
        if (is_array($value)) {
            /** @var \OpenDxp\Model\DataObject\ClassDefinition\Data\EncryptedField $fd */
            $fd = $params['fieldDefinition'];
            $delegateFd = $fd->getDelegate();
            if ($this->marshallerService->supportsFielddefinition('classificationstore', $delegateFd->getFieldtype())) {
                $marshaller = $this->marshallerService->buildFieldefinitionMarshaller('classificationstore', $delegateFd->getFieldtype());

                $encryptedValue = $this->decrypt($value['value'], $params);
                $encryptedValue2 = $this->decrypt($value['value2'], $params);

                return $marshaller->unmarshal([
                    'value' => $encryptedValue,
                    'value2' => $encryptedValue2,
                ], ['fieldDefinition' => $delegateFd, 'format' => 'classificationstore']);
            }
            return $this->decrypt($value['value'], $params);
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
