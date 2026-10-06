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

namespace OpenDxp\Tests\Unit\Model;

use InvalidArgumentException;
use OpenDxp\Model\Site;

beforeEach(fn () => $this->site = new Site());

it('returns no custom settings while it has none', function () {
    $settings = $this->site->getCustomSettings();

    expect($settings)->toBe([]);
});

it('returns the custom settings of one scope', function () {
    $this->site->setCustomSettings([
        'myBundle' => [
            'color' => 'red',
            'size' => 42,
        ],
    ]);

    $settings = $this->site->getCustomSettings('myBundle');

    expect($settings)->toBe([
        'color' => 'red',
        'size' => 42,
    ]);
});

it('returns no custom settings for a scope it has none for', function () {
    $this->site->setCustomSettings(['myBundle' => ['key' => 'value']]);

    $settings = $this->site->getCustomSettings('unknownBundle');

    expect($settings)->toBe([]);
});

it('returns every scope when none is named', function () {
    $this->site->setCustomSettings([
        'bundleA' => ['x' => 1],
        'bundleB' => ['y' => 2],
    ]);

    $settings = $this->site->getCustomSettings();

    expect($settings)->toBe([
        'bundleA' => ['x' => 1],
        'bundleB' => ['y' => 2],
    ]);
});

it('forgets its custom settings when they are set to null', function () {
    $this->site->setCustomSettings(['myBundle' => ['key' => 'value']]);

    $this->site->setCustomSettings(null);

    expect($this->site->getCustomSettings('myBundle'))->toBe([]);
});

it('reads custom settings that arrive serialized', function () {
    $serialized = serialize(['bundleA' => ['active' => true]]);

    $this->site->setCustomSettings($serialized);

    expect($this->site->getCustomSettings('bundleA'))->toBe(['active' => true]);
});

it('accepts a domain with a wildcard', function () {
    $this->site->setDomains(['*.example.com']);

    expect($this->site->getDomains())->toBe(['*.example.com']);
});

it('refuses an invalid domain', function () {
    $this->site->setDomains(['not a valid domain!!']);
})->throws(InvalidArgumentException::class, 'Invalid domain name "not a valid domain!!"');
