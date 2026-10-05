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
use OpenDxp\Model\Document\Link;

/**
 * @extends AbstractDocumentFactory<Link>
 *
 * @method Link create(array|callable $attributes = [])
 * @method static Link createOne(array $attributes = [])
 * @method static list<Link> createMany(int $number, array $attributes = [])
 */
final class DocumentLinkFactory extends AbstractDocumentFactory
{
    public static function class(): string
    {
        return Link::class;
    }

    public function withTarget(Document $target): static
    {
        return $this->with(['internal' => $target->getId()]);
    }

    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'key'          => sprintf('link-%s', uniqid()),
            'type'         => 'link',
            'linktype'     => 'internal',
            'internalType' => 'document',
        ];
    }

    protected function initialize(): static
    {
        return parent::initialize()->withNavigationName();
    }
}
