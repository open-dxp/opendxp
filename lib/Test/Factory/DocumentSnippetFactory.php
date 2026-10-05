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

use OpenDxp\Model\Document\Snippet;

/**
 * @extends AbstractPageSnippetFactory<Snippet>
 *
 * @method Snippet create(array|callable $attributes = [])
 * @method static Snippet createOne(array $attributes = [])
 * @method static list<Snippet> createMany(int $number, array $attributes = [])
 */
final class DocumentSnippetFactory extends AbstractPageSnippetFactory
{
    public static function class(): string
    {
        return Snippet::class;
    }

    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'key'  => sprintf('snippet-%s', uniqid()),
            'type' => 'snippet',
        ];
    }
}
