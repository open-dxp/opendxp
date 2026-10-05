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

use OpenDxp\Model\Asset\Document;

/**
 * @extends AbstractElementFactory<Document>
 *
 * @method Document create(array|callable $attributes = [])
 * @method static Document createOne(array $attributes = [])
 * @method static list<Document> createMany(int $number, array $attributes = [])
 */
final class AssetDocumentFactory extends AbstractElementFactory
{
    public static function class(): string
    {
        return Document::class;
    }

    public static function fixture(): string
    {
        return dirname(__DIR__) . '/Fixtures/document.pdf';
    }

    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'type'     => 'document',
            'filename' => sprintf('document-%s.pdf', uniqid()),
            'data'     => file_get_contents(self::fixture()),
        ];
    }
}
