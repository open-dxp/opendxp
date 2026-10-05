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

it('carries no custom settings to begin with', function () {

    $site = new Site();

    expect($site->getCustomSettings())
        ->toBe([])
        ->and($site->getCustomSettings('anyScope'))
        ->toBe([]);
});

it('hands back the custom settings of one scope', function () {

    $site = new Site();
    $site->setCustomSettings(['myBundle' => ['color' => 'red', 'size' => 42]]);

    expect($site->getCustomSettings('myBundle'))->toBe(['color' => 'red', 'size' => 42]);
});

it('hands back nothing for a scope it carries no settings for', function () {

    $site = new Site();
    $site->setCustomSettings(['myBundle' => ['key' => 'val']]);

    expect($site->getCustomSettings('unknownBundle'))->toBe([]);
});

it('hands back every scope when none is named', function () {

    $settings = ['bundleA' => ['x' => 1], 'bundleB' => ['y' => 2]];
    $site = new Site();
    $site->setCustomSettings($settings);

    expect($site->getCustomSettings())->toBe($settings);
});

it('carries no custom settings once they were set to nothing', function () {

    $site = new Site();
    $site->setCustomSettings(['foo' => 'bar']);
    $site->setCustomSettings(null);

    expect($site->getCustomSettings())
        ->toBe([])
        ->and($site->getCustomSettings('foo'))
        ->toBe([]);
});

it('reads custom settings that were handed over serialized', function () {

    $site = new Site();
    $site->setCustomSettings(serialize(['bundleA' => ['active' => true]]));

    expect($site->getCustomSettings('bundleA'))->toBe(['active' => true]);
});

it('takes a domain with a wildcard', function () {

    $site = new Site();
    $site->setDomains(['*.example.com']);

    expect($site->getDomains())->toBe(['*.example.com']);
});

it('refuses a domain that is not one', function () {
    (new Site())->setDomains(['not a valid domain!!']);
})->throws(InvalidArgumentException::class);
