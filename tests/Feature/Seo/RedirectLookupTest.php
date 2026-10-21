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

use OpenDxp\Bundle\SeoBundle\Redirect\RedirectHandler;
use OpenDxp\Cache;
use OpenDxp\Db;
use OpenDxp\Test\Factory\RedirectFactory;
use OpenDxp\TestFoundation\Container;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns the number of queries the work sends. The second status query counts itself, so it is taken off.
 */
function queriesOf(callable $work): int
{
    $questions = static function (): int {
        $status = Db::get()->fetchAssociative("SHOW SESSION STATUS LIKE 'Questions'");

        return (int) $status['Value'];
    };

    $before = $questions();
    $work();

    return $questions() - $before - 1;
}

/**
 * Looks the request up twice, so the second lookup reads the redirects from the cache.
 */
function queriesOfLookup(string $uri, bool $override): int
{
    resetServices();
    Container::get(RedirectHandler::class)->checkForRedirect(Request::create($uri), $override);
    resetServices();
    $handler = Container::get(RedirectHandler::class);

    return queriesOf(fn () => $handler->checkForRedirect(Request::create($uri), $override));
}

it('looks a page up before routing in the cache alone', function (int $redirects) {
    RedirectFactory::createMany($redirects);
    RedirectFactory::new()
        ->matching('@^/pattern/(.*)$@')
        ->create(['target' => '/other/$1']);

    $queries = queriesOfLookup('/some/page', override: true);

    // The tag-aware cache reads the item and the version of its tag.
    expect($queries)->toBeLessThanOrEqual(2);
})->with([
    'with one redirect' => [1],
    'with a hundred redirects' => [100],
]);

it('asks the database once for an unknown URL', function (int $redirects) {
    RedirectFactory::createMany($redirects);

    $queries = queriesOfLookup('/does/not/exist', override: false);

    expect($queries)->toBeLessThanOrEqual(3);
})->with([
    'with one redirect' => [1],
    'with a hundred redirects' => [100],
]);

it('asks the database before routing while a source has priority 99', function () {
    RedirectFactory::new()
        ->withPriority(99)
        ->create(['source' => '/overriding']);

    $queries = queriesOfLookup('/some/page', override: true);

    expect($queries)->toBe(3);
});

it('follows a changed redirect on the next request', function () {
    $redirect = RedirectFactory::createOne([
        'source' => '/changing',
        'target' => '/before',
    ]);
    resetServices();
    answerTo('/changing');
    $redirect->setTarget('/after');
    $redirect->save();
    resetServices();

    $response = answerTo('/changing');

    expect($response)->toRedirectTo('/after');
});

it('stops redirecting once a redirect is deleted', function () {
    $redirect = RedirectFactory::createOne(['source' => '/deleted']);
    resetServices();
    answerTo('/deleted');
    $redirect->delete();
    resetServices();

    $response = answerTo('/deleted');

    expect($response)->toBeAnsweredWithoutRedirect();
});

it('reads only the cache while the SEO bundle is not installed', function () {
    RedirectFactory::createOne(['source' => '/uninstalled']);
    cacheRedirectsAsNotInstalled();

    $queries = queriesOfLookup('/uninstalled', override: false);

    expect($queries)->toBeLessThanOrEqual(2);
});

it('redirects again once the tag redirect is cleared, as an installation does', function () {
    RedirectFactory::createOne([
        'source' => '/installed',
        'target' => '/target',
    ]);
    cacheRedirectsAsNotInstalled();
    Cache::clearTag('redirect');
    resetServices();

    $response = answerTo('/installed');

    expect($response)->toRedirectTo('/target');
});
