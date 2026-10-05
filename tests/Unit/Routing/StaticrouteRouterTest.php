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


namespace OpenDxp\Tests\Unit\Routing;

use OpenDxp\Bundle\StaticRoutesBundle\Model\Staticroute;

afterEach(fn () => Staticroute::setCurrentRoute(null));

it('leaves the locale of the context out of the matched parameters', function () {

    $router = staticrouteRouter(staticroute('/\/(\w+)\/product$/', 'lang'));
    $params = $router->match('/de/product');

    expect($params)
        ->toHaveKey('lang', 'de')
        ->and($params)
        ->not->toHaveKey('_locale');
});

it('keeps a locale the pattern itself matched', function () {

    $router = staticrouteRouter(staticroute('/\/(\w+)\/product$/', '_locale'));

    expect($router->match('/de/product'))->toHaveKey('_locale', 'de');
});

it('reads the locale from the variable a route declares for it', function () {

    $router = staticrouteRouter(staticroute('/\/(\w+)\/product$/', 'language'));
    $router->setLocaleParams(['language']);

    expect($router->match('/de/product'))->toHaveKey('_locale', 'de');
});
