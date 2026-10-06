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

use Doctrine\DBAL\Connection;
use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use OpenDxp\Bundle\SeoBundle\Redirect\RedirectHandler;
use OpenDxp\Bundle\SeoBundle\Redirect\RedirectHitCounter;
use OpenDxp\Bundle\SeoBundle\Redirect\RedirectTableProvider;
use OpenDxp\Cache;
use OpenDxp\Db;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Test\Factory\RedirectFactory;
use OpenDxp\TestFoundation\Container;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Lock\LockFactory;

/**
 * The second status query counts itself, so it is taken off.
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

it('looks a request up with a single cache read', function (string $uri, bool $override) {
    RedirectFactory::createOne(['source' => '/listed']);
    RedirectFactory::new()
        ->matching('@^/pattern/(.*)$@')
        ->create(['target' => '/other/$1']);
    RedirectFactory::new()
        ->withPriority(99)
        ->create(['source' => '/overriding']);
    $warmUp = Request::create($uri);
    $request = Request::create($uri);
    resetServices();
    Container::get(RedirectHandler::class)->checkForRedirect($warmUp, $override);
    resetServices();
    $handler = Container::get(RedirectHandler::class);

    $queries = queriesOf(fn () => $handler->checkForRedirect($request, $override));

    // The tag-aware cache reads the revision and the version of its tag.
    expect($queries)->toBeLessThanOrEqual(2);
})->with([
    'a page before routing' => ['/some/page', true],
    'an unknown URL after routing' => ['/does/not/exist', false],
    'an exact source' => ['/listed', false],
    'a regular expression' => ['/pattern/x', false],
]);

it('looks a request up with a single cache read when there are no redirects', function () {
    $warmUp = Request::create('/page');
    $request = Request::create('/page');
    resetServices();
    Container::get(RedirectHandler::class)->checkForRedirect($warmUp, override: true);
    resetServices();
    $handler = Container::get(RedirectHandler::class);

    $queries = queriesOf(fn () => $handler->checkForRedirect($request, override: true));

    // The tag-aware cache reads the revision and the version of its tag.
    expect($queries)->toBeLessThanOrEqual(2);
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

    expect($response)->toComeFromNoRedirect();
});

it('falls back to a table without redirects when the redirects cannot be read', function () {
    $connection = $this->createMock(Connection::class);
    $connection
        ->method('iterateAssociative')
        ->willThrowException(new RuntimeException('Unknown column'));
    // A revision left in the cache would serve the table of an earlier test from its file.
    Cache::remove('seo_redirect_table_revision');
    $tables = new RedirectTableProvider(
        $connection,
        Container::get(LockFactory::class),
        new NullLogger(),
        sprintf('%s/redirect-table-%s', sys_get_temp_dir(), uniqid()),
    );

    $table = $tables->get();

    expect($table->isInstalled())->toBeFalse();
});

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
        headers: [RedirectHandler::RESPONSE_HEADER_NAME_ID => (string) $redirect->getId()],
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
