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

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use OpenDxp\Model;
use Override;

class Email extends Model\DataObject\ClassDefinition\Data\Input
{
    #[Override]
    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if (!$omitMandatoryCheck && is_string($data) && $data !== '') {
            $validator = new EmailValidator();
            if (!$validator->isValid($data, new RFCValidation())) {
                throw new Model\Element\ValidationException('Value in field [ ' . $this->getName() . " ] isn't a valid email address");
            }
        }

        parent::checkValidity($data, $omitMandatoryCheck);
    }

    #[Override]
    public function getFieldType(): string
    {
        return 'email';
    }
}
