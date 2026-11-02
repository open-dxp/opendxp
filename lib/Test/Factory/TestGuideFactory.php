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

namespace OpenDxp\Test\Factory;

use OpenDxp\Model\DataObject\Data\BlockElement;
use OpenDxp\Model\DataObject\Localizedfield;
use OpenDxp\Model\DataObject\TestGuide;

/**
 * @extends AbstractDataObjectFactory<TestGuide>
 */
final class TestGuideFactory extends AbstractDataObjectFactory
{
    public static function class(): string
    {
        return TestGuide::class;
    }

    public function withSections(string ...$headings): static
    {
        return $this->with(['sections' => array_map(self::section(...), $headings)]);
    }

    /**
     * @return array<string, BlockElement>
     */
    private static function section(string $heading): array
    {
        $localizedFields = new Localizedfield(['de' => ['heading' => $heading]]);

        return ['localizedfields' => new BlockElement('localizedfields', 'localizedfields', $localizedFields)];
    }
}
