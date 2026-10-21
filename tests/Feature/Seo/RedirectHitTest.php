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

namespace OpenDxp\Tests\Feature\Seo;

use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use OpenDxp\Bundle\SeoBundle\Redirect\RedirectHandler;
use OpenDxp\Bundle\SeoBundle\Redirect\RedirectHitCounter;
use OpenDxp\Db;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Test\Factory\RedirectFactory;
use OpenDxp\TestFoundation\Container;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

function hitsOf(Redirect $redirect): int
{
    return (int) Db::get()->fetchOne(
        'SELECT hits FROM redirect_hits WHERE redirectId = ?',
        [$redirect->getId()],
    );
}

function lastHitOf(Redirect $redirect): ?int
{
    $lastHit = Db::get()->fetchOne(
        'SELECT lastHit FROM redirect_hits WHERE redirectId = ?',
        [$redirect->getId()],
    );

    return $lastHit === false || $lastHit === null ? null : (int) $lastHit;
}

it('counts a hit of a redirect', function () {
    $redirect = RedirectFactory::createOne(['source' => '/counted']);
    $before = time();
    resetServices();

    answerTo('/counted');

    expect(hitsOf($redirect))
        ->toBe(1)
        ->and(lastHitOf($redirect))
        ->toBeGreaterThanOrEqual($before);
});

it('adds a hit to the hits counted before', function () {
    $redirect = RedirectFactory::createOne(['source' => '/counted']);
    recordHits($redirect, 5, 1700000000);
    resetServices();

    answerTo('/counted');

    expect(hitsOf($redirect))->toBe(6);
});

it('counts a hit of a redirect that overrides an existing page', function () {
    $page = DocumentPageFactory::createOne();
    $redirect = RedirectFactory::new()
        ->withPriority(99)
        ->create(['source' => $page->getFullPath()]);
    resetServices();

    answerTo($page->getFullPath());

    expect(hitsOf($redirect))->toBe(1);
});

it('counts no hit when a redirect is only looked up', function () {
    $redirect = RedirectFactory::createOne(['source' => '/looked-up']);
    $request = Request::create('/looked-up');
    resetServices();

    Container::get(RedirectHandler::class)->checkForRedirect($request);
    Container::get(RedirectHitCounter::class)->flush();

    expect(hitsOf($redirect))->toBe(0);
});

it('counts no hit when counting is switched off', function () {
    $redirect = RedirectFactory::createOne();
    $response = new Response(
        status: 301,
        headers: ['X-OpenDxp-Redirect-ID' => (string) $redirect->getId()],
    );
    $counter = new RedirectHitCounter(
        Db::get(),
        new NullLogger(),
        ['count_hits' => false],
    );

    $counter->count($response);
    $counter->flush();

    expect(hitsOf($redirect))->toBe(0);
});

it('forgets the hits of a deleted redirect', function () {
    $redirect = RedirectFactory::createOne();
    recordHits($redirect, 3, time());

    $redirect->delete();

    expect(hitsOf($redirect))->toBe(0);
});
