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

it('looks a request up with a single cache read', function (string $uri, bool $beforeRouting) {
    RedirectFactory::createOne([
        'source' => '/listed',
        'target' => '/somewhere',
    ]);
    RedirectFactory::createOne([
        'source' => '@^/pattern/(.*)$@',
        'target' => '/other/$1',
        'regex' => true,
    ]);
    RedirectFactory::createOne([
        'source' => '/overriding',
        'target' => '/first',
        'priority' => 99,
    ]);

    nextRequest();
    Container::get(RedirectHandler::class)->checkForRedirect(Request::create($uri), $beforeRouting);

    nextRequest();
    $handler = Container::get(RedirectHandler::class);

    // The tag-aware cache reads the revision and the version of its tag.
    expect(queriesOf(fn () => $handler->checkForRedirect(Request::create($uri), $beforeRouting)))
        ->toBeLessThanOrEqual(2);
})->with([
    'a page, before routing' => ['/some/page', true],
    'an unknown URL, after routing' => ['/does/not/exist', false],
    'an exact source' => ['/listed', false],
    'a regular expression' => ['/pattern/x', false],
]);

it('looks a request up with a single cache read when there are no redirects', function () {
    nextRequest();
    Container::get(RedirectHandler::class)->checkForRedirect(Request::create('/page'), true);

    nextRequest();
    $handler = Container::get(RedirectHandler::class);

    // The tag-aware cache reads the revision and the version of its tag.
    expect(queriesOf(fn () => $handler->checkForRedirect(Request::create('/page'), true)))
        ->toBeLessThanOrEqual(2);
});

it('follows a changed redirect on the next request', function () {
    $redirect = RedirectFactory::createOne([
        'source' => '/changing',
        'target' => '/before',
    ]);
    nextRequest();

    expect(answerTo('/changing'))
        ->toRedirectTo('/before');

    $redirect->setTarget('/after');
    $redirect->save();
    nextRequest();

    expect(answerTo('/changing'))
        ->toRedirectTo('/after');
});

it('stops redirecting once a redirect is deleted', function () {
    $redirect = RedirectFactory::createOne([
        'source' => '/deleted',
        'target' => '/gone',
    ]);
    nextRequest();

    expect(answerTo('/deleted'))
        ->toBeAnsweredBy($redirect);

    $redirect->delete();
    nextRequest();

    expect(answerTo('/deleted'))
        ->toBeAnsweredBy(null);
});

it('answers without redirects when the redirects cannot be read', function () {
    $connection = $this->createMock(Connection::class);
    $connection
        ->method('iterateAssociative')
        ->willThrowException(new RuntimeException('Unknown column'));
    Cache::remove('seo_redirect_table_revision');

    $tables = new RedirectTableProvider(
        $connection,
        Container::get(LockFactory::class),
        new NullLogger(),
        sys_get_temp_dir() . '/redirect-table-' . uniqid(),
    );

    expect($tables->get()->isInstalled())
        ->toBeFalse();
});

it('counts the hits of a redirect', function () {
    $page = DocumentPageFactory::createOne([
        'key' => 'counted-' . uniqid(),
        'published' => true,
    ]);
    $exact = RedirectFactory::createOne([
        'source' => '/counted',
        'target' => '/target',
    ]);
    $overriding = RedirectFactory::createOne([
        'source' => $page->getFullPath(),
        'target' => '/target',
        'priority' => 99,
    ]);
    nextRequest();

    answerTo('/counted');
    answerTo('/counted');
    answerTo($page->getFullPath());

    expect(hitsOf($exact))
        ->toBe(2)
        ->and(lastHitOf($exact))
        ->toBeGreaterThanOrEqual(time() - 60)
        ->and(hitsOf($overriding))
        ->toBe(1);
});

it('counts no hit when a redirect is only looked up', function () {
    $redirect = RedirectFactory::createOne([
        'source' => '/looked-up',
        'target' => '/target',
    ]);
    nextRequest();

    Container::get(RedirectHandler::class)->checkForRedirect(Request::create('/looked-up'));
    Container::get(RedirectHitCounter::class)->flush();

    expect(hitsOf($redirect))
        ->toBe(0);
});

it('counts no hit when counting is switched off', function () {
    $redirect = RedirectFactory::createOne([
        'source' => '/uncounted',
        'target' => '/target',
    ]);
    $response = new Response(status: 301, headers: [
        RedirectHandler::RESPONSE_HEADER_NAME_ID => (string) $redirect->getId(),
    ]);

    $counter = new RedirectHitCounter(Db::get(), new NullLogger(), [
        'count_hits' => false,
    ]);
    $counter->count($response);
    $counter->flush();

    expect(hitsOf($redirect))
        ->toBe(0);
});

it('forgets the hits of a deleted redirect', function () {
    $redirect = RedirectFactory::createOne([
        'source' => '/forgotten',
        'target' => '/target',
    ]);
    nextRequest();
    answerTo('/forgotten');

    $redirect->delete();

    expect(hitsOf($redirect))
        ->toBe(0);
});
