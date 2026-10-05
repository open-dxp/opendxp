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

namespace OpenDxp\Test\Factory;

use OpenDxp\Bundle\GlossaryBundle\Model\Glossary;

/**
 * @extends AbstractSavingFactory<Glossary>
 *
 * @method Glossary create(array|callable $attributes = [])
 * @method static Glossary createOne(array $attributes = [])
 * @method static list<Glossary> createMany(int $number, array $attributes = [])
 */
final class GlossaryFactory extends AbstractSavingFactory
{
    public static function class(): string
    {
        return Glossary::class;
    }

    protected function defaults(): array
    {
        return [
            'text'     => sprintf('term-%s', uniqid()),
            'link'     => '/test',
            'language' => 'en',
        ];
    }
}
