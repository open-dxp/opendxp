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

use OpenDxp\Model;
use OpenDxp\Model\DataObject\ClassDefinition\DynamicOptionsProvider\CountryOptionsProvider;

class Countrymultiselect extends Model\DataObject\ClassDefinition\Data\Multiselect
{
    /**
     * Restrict selection to comma-separated list of countries.
     *
     * @internal
     *
     */
    public ?string $restrictTo = null;

    public function setRestrictTo(array|string|null $restrictTo): void
    {
        /**
         * @extjs6
         */
        if (is_array($restrictTo)) {
            $restrictTo = implode(',', $restrictTo);
        }

        $this->restrictTo = $restrictTo;
    }

    public function getRestrictTo(): ?string
    {
        return $this->restrictTo;
    }

    public function getOptionsProviderClass(): string
    {
        return '@' . CountryOptionsProvider::class;
    }

    #[\Override]
    public function getFieldType(): string
    {
        return 'countrymultiselect';
    }
}
