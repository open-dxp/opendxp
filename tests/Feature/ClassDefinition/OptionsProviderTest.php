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

namespace OpenDxp\Tests\Feature\ClassDefinition\OptionsProvider;

use OpenDxp\Model\DataObject\ClassDefinition\Helper\OptionsProviderResolver;
use OpenDxp\TestFoundation\Container;
use OpenDxp\Tests\Application\Service\FixedOptionsProvider;

function provider(): ?object
{
    return OptionsProviderResolver::resolveProvider(
        sprintf('@%s', FixedOptionsProvider::class),
        OptionsProviderResolver::MODE_SELECT,
    );
}

it('hands out the options provider of the container the kernel runs now', function () {
    provider();
    self::ensureKernelShutdown();
    self::bootKernel();

    expect(provider())->toBe(Container::get(FixedOptionsProvider::class));
});
