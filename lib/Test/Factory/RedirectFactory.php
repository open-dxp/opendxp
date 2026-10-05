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

use OpenDxp\Bundle\SeoBundle\Model\Redirect;

/**
 * @extends AbstractSavingFactory<Redirect>
 */
final class RedirectFactory extends AbstractSavingFactory
{
    public static function class(): string
    {
        return Redirect::class;
    }

    protected function defaults(): array
    {
        return [
            'type'       => Redirect::TYPE_PATH,
            'source'     => sprintf('/redirect-%s', uniqid()),
            'target'     => '/',
            'statusCode' => 301,
            'priority'   => 1,
            'active'     => true,
        ];
    }
}
