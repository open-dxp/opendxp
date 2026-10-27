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

namespace OpenDxp\Tests\Unit\Extension\Bundle;

use OpenDxp\Composer\PackageInfo;
use OpenDxp\Extension\Bundle\OpenDxpBundleLocator;

it('finds no bundles without search paths', function () {
    $locator = new OpenDxpBundleLocator(new PackageInfo(), [], false);

    expect($locator->findBundles())->toBe([]);
});

it('skips search paths that do not exist', function () {
    $locator = new OpenDxpBundleLocator(new PackageInfo(), ['/does/not/exist'], false);

    expect($locator->findBundles())->toBe([]);
});
