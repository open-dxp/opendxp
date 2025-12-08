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

namespace OpenDxp\Model\DataObject\ClassDefinition\Data;

use Exception;
use OpenDxp\Config;
use OpenDxp\Model;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Normalizer\NormalizerInterface;
use Symfony\Component\PasswordHasher\Hasher\CheckPasswordLengthTrait;

class Password extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface
{
    use CheckPasswordLengthTrait;
    use DataObject\Traits\SimpleComparisonTrait;
    use DataObject\Traits\DataWidthTrait;
    use DataObject\Traits\SimpleNormalizerTrait;

    private const string MASK = '******';

    public ?int $minimumLength = null;

    public function getMinimumLength(): ?int
    {
        return $this->minimumLength;
    }

    public function setMinimumLength(?int $minimumLength): void
    {
        $this->minimumLength = $minimumLength;
    }

    /**
     *
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     */
    public function getDataForResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?string
    {
        if (empty($data)) {
            return null;
        }

        $value = (string) $data;

        // Already a password_* hash? then do not rehash.
        if ($this->isPasswordStyleHash($value)) {
            return $value;
        }

        $hashed = $this->calculateHash($value);

        /** set the hashed password back to the object, to be sure that is not plain-text after the first save
         this is especially to avoid plaintext passwords in the search-index */

        // a model should be switched if the owner parameter is used,
        // for example: field collections would use \OpenDxp\Model\DataObject\Fieldcollection\Data\Dao
        $passwordModel = array_key_exists('owner', $params)
            ? $params['owner']
            : ($object ?: null);

        if (null !== $passwordModel && !$this->isNonWritablePasswordContainer($passwordModel)) {
            $setter = $this->composeSetter();
            $passwordModel->$setter($hashed);
        }

        return $hashed;
    }

    /**
     * Calculate hash according to configured parameters
     *
     *
     *
     * @internal
     */
    public function calculateHash(string $data): string
    {
        [$algo, $options] = $this->currentHashSettings();

        return password_hash($data, $algo, $options);
    }

    /**
     * Verify password. Optionally re-hash the password if needed.
     *
     * Re-hash will be performed if PHP's password_hash default params (algorithm, cost) differ
     * from the ones which were used to create the hash (e.g. cost was increased from 10 to 12).
     * In this case, the hash will be re-calculated with the new parameters and saved back to the object.
     *
     *
     * @internal
     */
    public function verifyPassword(string $password, DataObject\Concrete $object, bool $updateHash = true): bool
    {
        $getter = $this->composeGetter();
        $setter = $this->composeSetter();

        $objectHash = (string) ($object->$getter() ?? '');
        if ($objectHash === '') {
            return false;
        }

        $result = password_verify($password, $objectHash);

        if ($result && $updateHash) {
            [$algo, $options] = $this->currentHashSettings();

            if (password_needs_rehash($objectHash, $algo, $options)) {
                $newHash = $this->calculateHash($password);

                $object->$setter($newHash);
                $object->save();
            }
        }

        return $result;
    }

    /**
     *
     *
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     */
    public function getDataFromResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?string
    {
        return $data;
    }

    /**
     *
     *
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     */
    public function getDataForQueryResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?string
    {
        return $this->getDataForResource($data, $object, $params);
    }

    public function getDataForEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?string
    {
        return $data;
    }

    /**
     * @see Data::getDataFromEditmode
     *
     */
    public function getDataFromEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?string
    {
        if ($data === '') {
            return null;
        }

        return $data;
    }

    /**
     *
     *
     * @see Data::getVersionPreview
     *
     */
    #[\Override]
    public function getVersionPreview(mixed $data, ?DataObject\Concrete $object = null, array $params = []): string
    {
        return self::MASK;
    }

    public function getDataForGrid(?string $data, Concrete $object, array $params = []): string
    {
        return self::MASK;
    }

    #[\Override]
    public function getDataForSearchIndex(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        return '';
    }

    #[\Override]
    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return true;
    }

    #[\Override]
    public function getDiffDataFromEditmode(array $data, ?DataObject\Concrete $object = null, array $params = []): mixed
    {
        return $data[0]['data'];
    }

    /** See parent class.
     *
     */
    #[\Override]
    public function getDiffDataForEditMode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?array
    {
        $diffdata = [];
        $diffdata['data'] = $data;
        $diffdata['disabled'] = !($this->isDiffChangeAllowed($object, $params));
        $diffdata['field'] = $this->getName();
        $diffdata['key'] = $this->getName();
        $diffdata['type'] = $this->getFieldType();

        if ($data) {
            $diffdata['value'] = $this->getVersionPreview($data, $object, $params);
            // $diffdata["value"] = $data;
        }

        $diffdata['title'] = empty($this->title) ? $this->name : $this->title;

        $result = [];
        $result[] = $diffdata;

        return $result;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?string';
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?string';
    }

    public function getPhpdocInputType(): ?string
    {
        return 'string|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return 'string|null';
    }

    /**
     *
     * @throws Model\Element\ValidationException|Exception
     */
    #[\Override]
    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if (is_string($data) && $this->isPasswordTooLong($data)) {
            throw new Model\Element\ValidationException('Value in field [ ' . $this->getName() . ' ] is too long');
        }

        if (!$omitMandatoryCheck && ($this->getMinimumLength() && is_string($data) && strlen($data) < $this->getMinimumLength())) {
            throw new Model\Element\ValidationException('Value in field [ ' . $this->getName() . ' ] is not at least ' . $this->getMinimumLength() . ' characters');
        }

        parent::checkValidity($data, $omitMandatoryCheck, $params);
    }

    public function getColumnType(): string
    {
        return 'varchar(255)';
    }

    public function getQueryColumnType(): string
    {
        return $this->getColumnType();
    }

    public function getFieldType(): string
    {
        return 'password';
    }

    private function isPasswordStyleHash(string $value): bool
    {
        $info = password_get_info($value);

        return $info['algo'] !== null && $info['algo'] !== 0;
    }

    /**
     * @return array{0:int|string,1:array}
     */
    private function currentHashSettings(): array
    {
        $cfg = Config::getSystemConfiguration()['security']['password'] ?? [];

        $algo = $cfg['algorithm'] ?? PASSWORD_DEFAULT;
        $opts = $cfg['options'] ?? [];

        return [$algo, $opts];
    }

    private function isNonWritablePasswordContainer(object $owner): bool
    {
        return $owner instanceof DataObject\Classificationstore
            || $owner instanceof DataObject\Localizedfield;
    }

    private function composeSetter(): string
    {
        return 'set' . ucfirst($this->getName());
    }

    private function composeGetter(): string
    {
        return 'get' . ucfirst($this->getName());
    }
}
