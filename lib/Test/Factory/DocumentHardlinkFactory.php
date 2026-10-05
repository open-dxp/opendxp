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

use OpenDxp\Model\Document;
use OpenDxp\Model\Document\Hardlink;

/**
 * @extends AbstractDocumentFactory<Hardlink>
 *
 * @method Hardlink create(array|callable $attributes = [])
 * @method static Hardlink createOne(array $attributes = [])
 * @method static list<Hardlink> createMany(int $number, array $attributes = [])
 */
final class DocumentHardlinkFactory extends AbstractDocumentFactory
{
    public static function class(): string
    {
        return Hardlink::class;
    }

    public function withSource(Document $source): static
    {
        return $this->with([
            'sourceId'             => $source->getId(),
            'propertiesFromSource' => true,
            'childrenFromSource'   => true,
        ]);
    }

    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'key'  => sprintf('hardlink-%s', uniqid()),
            'type' => 'hardlink',
        ];
    }
}
