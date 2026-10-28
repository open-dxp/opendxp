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

namespace OpenDxp\Tests\Unit\InstallBundle;

use OpenDxp\Bundle\InstallBundle\Event\BundleSetupEvent;

beforeEach(fn () => $this->event = new BundleSetupEvent(
    ['CoreBundle' => 'App\CoreBundle'],
    ['CoreBundle'],
    [
        'NewsBundle' => 'App\NewsBundle',
        'MembersBundle' => 'App\MembersBundle',
        'AlsoCoreBundle' => 'App\CoreBundle',
    ],
));

it('leaves out extension bundles that are core bundles', function () {
    expect($this->event->getExtensionBundles())->toBe([
        'NewsBundle' => 'App\NewsBundle',
        'MembersBundle' => 'App\MembersBundle',
    ]);
});

it('leaves out extension bundles that are required', function () {
    $this->event->addRequiredBundle('Members', 'App\MembersBundle');

    expect($this->event->getExtensionBundles())->toBe(['NewsBundle' => 'App\NewsBundle']);
});

it('leaves out extension bundles that a subscriber adds as installable', function () {
    $this->event->addInstallableBundle('News', 'App\NewsBundle');

    expect($this->event->getExtensionBundles())->toBe(['MembersBundle' => 'App\MembersBundle']);
});

it('removes an extension bundle by its key', function () {
    $this->event->removeInstallableBundle('NewsBundle');

    expect($this->event->getExtensionBundles())->toBe(['MembersBundle' => 'App\MembersBundle']);
});

it('clears the extension bundles with the bundles and recommendations', function () {
    $this->event->clearBundlesAndRecommendations();

    expect($this->event->getBundles())->toBe([])
        ->and($this->event->getRecommendedBundles())->toBe([])
        ->and($this->event->getExtensionBundles())->toBe([]);
});

it('makes extension bundles available', function () {
    $this->event->addRequiredBundle('RequiredBundle', 'App\RequiredBundle');

    expect($this->event->getAvailableBundles())->toBe([
        'RequiredBundle' => 'App\RequiredBundle',
        'CoreBundle' => 'App\CoreBundle',
        'NewsBundle' => 'App\NewsBundle',
        'MembersBundle' => 'App\MembersBundle',
    ]);
});

it('keeps working without extension bundles', function () {
    $event = new BundleSetupEvent(['CoreBundle' => 'App\CoreBundle'], []);

    expect($event->getExtensionBundles())->toBe([])
        ->and($event->getAvailableBundles())->toBe(['CoreBundle' => 'App\CoreBundle']);
});
