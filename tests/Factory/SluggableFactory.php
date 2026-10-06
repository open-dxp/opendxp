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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Tests\Factory;

use OpenDxp\Model\DataObject\Sluggable;
use OpenDxp\Test\Factory\AbstractDataObjectFactory;

final class SluggableFactory extends AbstractDataObjectFactory
{
    public static function class(): string
    {
        return Sluggable::class;
    }

    /**
     * @param array<string, string> $names one name per language
     */
    public function withLocalizedNames(array $names): static
    {
        return $this->afterInstantiate(static function (Sluggable $object) use ($names): void {
            foreach ($names as $language => $name) {
                $object->setLname($name, $language);
            }
        });
    }
}
