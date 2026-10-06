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
use OpenDxp\Model\User;
use OpenDxp\Test\Factory\RedirectFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Browser;
use OpenDxp\Tests\Value\RedirectImport;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Browser\KernelBrowser;

/**
 * Sends what the redirect grid of the admin sends for an action on a redirect.
 *
 * @param array<string, mixed> $data
 */
function redirectGrid(User $user, string $action, array $data): KernelBrowser
{
    $url = sprintf('/admin/bundle/seo/redirects/list?xaction=%s', $action);

    return Browser::actingAs($user)
        ->post($url, [
            'body' => [
                'data' => json_encode($data),
            ],
        ]);
}

/**
 * @return array<string, mixed>
 */
function gridResponse(KernelBrowser $browser): array
{
    return json_decode(
        $browser->content(),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

function createdRedirect(KernelBrowser $browser): Redirect
{
    $response = gridResponse($browser);

    return Redirect::getById($response['data']['id']);
}

/**
 * @param array<string, string> $parameters
 *
 * @return array<int, array<string, mixed>>
 */
function filteredRedirects(User $user, array $parameters): array
{
    $browser = Browser::actingAs($user)
        ->post('/admin/bundle/seo/redirects/list', [
            'body' => [
                'limit' => '1000',
                ...$parameters,
            ],
        ]);
    $response = gridResponse($browser);

    return array_column($response['data'], column_key: null, index_key: 'id');
}

/**
 * @return array<int, array<string, mixed>>
 */
function listedRedirects(User $user): array
{
    return filteredRedirects($user, []);
}

function exportedRedirects(User $user): string
{
    return Browser::actingAs($user)
        ->visit('/admin/bundle/seo/redirects/csv-export')
        ->content();
}

/**
 * @param list<string> $lines
 */
function importRedirects(User $user, array $lines): RedirectImport
{
    $file = tempnam(sys_get_temp_dir(), 'redirects');
    file_put_contents($file, implode("\n", $lines));

    $upload = new UploadedFile($file, 'redirects.csv', 'text/csv', test: true);
    $browser = Browser::actingAs($user)
        ->post('/admin/bundle/seo/redirects/csv-import', [
            'files' => [
                'redirects' => $upload,
            ],
        ]);
    $statistics = gridResponse($browser)['data'];
    unlink($file);

    return new RedirectImport(
        $statistics['created'],
        $statistics['updated'],
        $statistics['errored'],
    );
}

/**
 * @return list<?string>
 */
function redirectSources(): array
{
    $redirects = new Redirect\Listing();

    return array_map(
        static fn (Redirect $redirect): ?string => $redirect->getSource(),
        $redirects->load(),
    );
}

beforeEach(function () {
    $this->editor = UserFactory::new()
        ->withPermissions('redirects')
        ->create();
    $this->seoSpecialist = UserFactory::new()
        ->withPermissions(
            'redirects',
            'redirects_protected',
        )
        ->create();
});

it('lists no protected redirect for an editor', function () {
    $open = RedirectFactory::createOne();
    $protected = RedirectFactory::new()
        ->protected()
        ->create();

    $listed = listedRedirects($this->editor);

    expect($listed)
        ->toHaveKey($open->getId())
        ->not->toHaveKey($protected->getId());
});

it('lists a protected redirect for a user who may manage it', function (User $user) {
    $protected = RedirectFactory::new()
        ->protected()
        ->create();

    $listed = listedRedirects($user);

    expect($listed)->toHaveKey($protected->getId());
})->with([
    'a user with the permission for protected redirects' => [fn () => $this->seoSpecialist],
    'an administrator' => [
        fn () => UserFactory::new()
            ->admin()
            ->create(),
    ],
]);

it('finds no protected redirect for an editor who tests a URL', function () {
    RedirectFactory::new()
        ->protected()
        ->create(['source' => '/tested']);
    resetServices();

    $found = filteredRedirects($this->editor, ['filter' => 'http://localhost/tested']);

    expect($found)->toBe([]);
});

it('keeps an editor from changing or deleting a protected redirect', function (string $action) {
    $protected = RedirectFactory::new()
        ->protected()
        ->create(['target' => '/kept-target']);

    $browser = redirectGrid($this->editor, $action, [
        'id' => $protected->getId(),
        'target' => '/changed',
    ]);

    expect($browser->client()->getResponse()->getStatusCode())
        ->toBe(403)
        ->and(Redirect::getById($protected->getId()))
        ->getTarget()
        ->toBe('/kept-target');
})->with([
    'updating' => ['update'],
    'deleting' => ['destroy'],
]);

it('keeps an editor from taking over the source of a protected redirect', function () {
    RedirectFactory::new()
        ->protected()
        ->create([
            'source' => '/relaunch',
            'target' => '/secret-target',
        ]);

    $browser = redirectGrid($this->editor, 'create', [
        'type' => Redirect::TYPE_PATH,
        'source' => '/Relaunch',
        'target' => '/mine',
        'statusCode' => 301,
        'priority' => 99,
        'active' => true,
    ]);

    expect(gridResponse($browser))
        ->success
        ->toBeFalse()
        ->errors
        ->toBe([
            [
                'field' => 'source',
                'message' => 'redirect_source_protected',
            ],
        ])
        ->and($browser->content())
        ->not->toContain('secret-target');
});

it('keeps an editor from protecting a redirect', function () {
    $browser = redirectGrid($this->editor, 'create', [
        'type' => Redirect::TYPE_PATH,
        'source' => '/editor',
        'target' => '/x',
        'statusCode' => 301,
        'priority' => 1,
        'active' => true,
        'protected' => true,
    ]);

    expect(createdRedirect($browser))
        ->isProtected()
        ->toBeFalse();
});

it('lets a user who may manage protected redirects protect one', function () {
    $browser = redirectGrid($this->seoSpecialist, 'create', [
        'type' => Redirect::TYPE_PATH,
        'source' => '/specialist',
        'target' => '/x',
        'statusCode' => 301,
        'priority' => 1,
        'active' => true,
        'protected' => true,
    ]);

    expect(createdRedirect($browser))
        ->isProtected()
        ->toBeTrue();
});

it('sets the owner and the creation date itself', function () {
    $before = time();

    $browser = redirectGrid($this->editor, 'create', [
        'type' => Redirect::TYPE_PATH,
        'source' => '/fields',
        'target' => '/x',
        'statusCode' => 301,
        'priority' => 1,
        'active' => true,
        'userOwner' => 4711,
        'creationDate' => 1,
    ]);

    expect(createdRedirect($browser))
        ->getUserOwner()
        ->toBe($this->editor->getId())
        ->getCreationDate()
        ->toBeGreaterThanOrEqual($before);
});

it('refuses a redirect that cannot work', function (array $values, string $field, string $message) {
    $browser = redirectGrid($this->seoSpecialist, 'create', [
        'type' => Redirect::TYPE_PATH,
        'statusCode' => 301,
        'priority' => 1,
        'active' => true,
        ...$values,
    ]);

    expect(gridResponse($browser))
        ->success
        ->toBeFalse()
        ->errors
        ->toContain([
            'field' => $field,
            'message' => $message,
        ]);
})->with([
    'an invalid regular expression' => [
        [
            'source' => '@^/broken(@',
            'target' => '/x',
            'regex' => true,
        ],
        'source',
        'redirect_regex_invalid',
    ],
    'a redirect to itself' => [
        [
            'source' => '/circle',
            'target' => '/Circle',
        ],
        'target',
        'redirect_loop',
    ],
    'a domain with a path' => [
        [
            'type' => Redirect::TYPE_DOMAIN,
            'source' => 'example.test/path',
            'target' => 'https://x.test/',
        ],
        'source',
        'redirect_domain_source_invalid',
    ],
    'a domain to a relative target' => [
        [
            'type' => Redirect::TYPE_DOMAIN,
            'source' => 'event.test',
            'target' => '/landing',
        ],
        'target',
        'redirect_domain_target_relative',
    ],
    'an expiry before the start' => [
        [
            'source' => '/later',
            'target' => '/x',
            'validFrom' => strtotime('+2 days'),
            'expiry' => strtotime('+1 day'),
        ],
        'expiry',
        'redirect_expires_before_start',
    ],
]);

it('refuses an empty row of the grid without failing', function () {
    $browser = redirectGrid($this->seoSpecialist, 'create', [
        'type' => null,
        'source' => '',
        'target' => null,
        'statusCode' => null,
        'priority' => null,
        'regex' => null,
        'active' => null,
        'passThroughParameters' => null,
        'sourceSite' => null,
        'targetSite' => null,
        'expiry' => null,
    ]);

    expect(gridResponse($browser))
        ->success
        ->toBeFalse()
        ->errors
        ->toContain([
            'field' => 'source',
            'message' => 'redirect_source_missing',
        ]);
});

it('fills the fields the grid leaves empty with defaults', function () {
    $browser = redirectGrid($this->seoSpecialist, 'create', [
        'type' => null,
        'source' => '/old',
        'target' => '/new',
        'statusCode' => null,
        'priority' => null,
    ]);

    expect(createdRedirect($browser))
        ->getType()
        ->toBe(Redirect::TYPE_PATH)
        ->getStatusCode()
        ->toBe(301)
        ->getPriority()
        ->toBe(1);
});

it('saves a start and an expiry that the grid sends as text', function () {
    $redirect = RedirectFactory::createOne();

    redirectGrid($this->seoSpecialist, 'update', [
        'id' => $redirect->getId(),
        'validFrom' => '1791410400',
        'expiry' => '1791496800',
    ]);

    expect(Redirect::getById($redirect->getId()))
        ->getValidFrom()
        ->toBe(1791410400)
        ->getExpiry()
        ->toBe(1791496800);
});

it('saves a redirect with a duplicate source and a chain and warns about both', function () {
    $duplicate = RedirectFactory::createOne([
        'source' => '/sale',
        'target' => '/shop',
    ]);
    $next = RedirectFactory::createOne([
        'source' => '/summer',
        'target' => '/beach',
    ]);

    $browser = redirectGrid($this->seoSpecialist, 'create', [
        'type' => Redirect::TYPE_PATH,
        'source' => '/sale',
        'target' => '/summer',
        'statusCode' => 302,
        'priority' => 5,
        'active' => true,
    ]);

    expect(gridResponse($browser))
        ->success
        ->toBeTrue()
        ->warnings
        ->toBe([
            [
                'message' => 'redirect_source_duplicate',
                'parameters' => ['%id%' => $duplicate->getId()],
            ],
            [
                'message' => 'redirect_chain',
                'parameters' => ['%id%' => $next->getId()],
            ],
        ])
        ->and(createdRedirect($browser))
        ->getTarget()
        ->toBe('/summer');
});

it('exports no protected redirect for an editor', function () {
    $protected = RedirectFactory::new()
        ->protected()
        ->create();

    $export = exportedRedirects($this->editor);

    expect($export)->not->toContain($protected->getSource());
});

it('exports a protected redirect for a user who may manage it', function () {
    $protected = RedirectFactory::new()
        ->protected()
        ->create();

    $export = exportedRedirects($this->seoSpecialist);

    expect($export)->toContain($protected->getSource());
});

it('keeps an editor from importing a protected redirect', function () {
    $result = importRedirects($this->editor, [
        'id;type;source;sourceSite;target;targetSite;statusCode;priority;regex;passThroughParameters;active;expiry;'
            . 'protected',
        ';path;/imported-protected;;/x;;301;1;0;0;1;;1',
        ';path;/imported-open;;/x;;301;1;0;0;1;;0',
    ]);

    expect($result)
        ->created
        ->toBe(1)
        ->errored
        ->toBe(1)
        ->and(redirectSources())
        ->toContain('/imported-open')
        ->not->toContain('/imported-protected');
});

it('imports a file of an older export without the new columns', function () {
    $result = importRedirects($this->seoSpecialist, [
        'id;type;source;sourceSite;target;targetSite;statusCode;priority;regex;passThroughParameters;active;expiry',
        ';path;/older-export;;/x;;301;1;0;0;1;',
    ]);

    expect($result)
        ->created
        ->toBe(1)
        ->errored
        ->toBe(0)
        ->and(redirectSources())
        ->toContain('/older-export');
});

it('lists the hits and the last hit of each redirect', function () {
    $hit = RedirectFactory::createOne();
    $unhit = RedirectFactory::createOne();
    recordHits($hit, 7, 1700000000);

    $listed = listedRedirects($this->editor);

    expect($listed[$hit->getId()])
        ->toMatchArray([
            'hits' => 7,
            'lastHit' => 1700000000,
        ])
        ->and($listed[$unhit->getId()])
        ->toMatchArray([
            'hits' => 0,
            'lastHit' => null,
        ]);
});

it('sorts the redirects by their hits', function () {
    $few = RedirectFactory::createOne();
    $many = RedirectFactory::createOne();
    recordHits($few, 1, time());
    recordHits($many, 100, time());

    $listed = filteredRedirects($this->editor, [
        'sort' => json_encode([
            [
                'property' => 'hits',
                'direction' => 'DESC',
            ],
        ]),
    ]);

    $order = array_intersect(
        array_keys($listed),
        [
            $few->getId(),
            $many->getId(),
        ],
    );
    expect(array_values($order))->toBe([
        $many->getId(),
        $few->getId(),
    ]);
});

it('shows a redirect in the views it belongs to', function (string $view, RedirectFactory $factory) {
    $redirect = $factory->create();

    $shown = filteredRedirects($this->seoSpecialist, ['show' => $view]);

    expect($shown)->toHaveKey($redirect->getId());
})->with([
    'an active one among the active ones' => [
        'active',
        fn () => RedirectFactory::new(),
    ],
    'an inactive one among the inactive ones' => [
        'inactive',
        fn () => RedirectFactory::new()
            ->inactive(),
    ],
    'an expired one among the expired ones' => [
        'expired',
        fn () => RedirectFactory::new()
            ->expired(),
    ],
    'one that starts later among the scheduled ones' => [
        'scheduled',
        fn () => RedirectFactory::new()
            ->scheduled(),
    ],
    'a protected one among the protected ones' => [
        'protected',
        fn () => RedirectFactory::new()
            ->protected(),
    ],
    'one that was never hit among the unused ones' => [
        'unused',
        fn () => RedirectFactory::new(),
    ],
]);

it('leaves a redirect out of the views it does not belong to', function (string $view, RedirectFactory $factory) {
    $redirect = $factory->create();

    $shown = filteredRedirects($this->seoSpecialist, ['show' => $view]);

    expect($shown)->not->toHaveKey($redirect->getId());
})->with([
    'an inactive one among the active ones' => [
        'active',
        fn () => RedirectFactory::new()
            ->inactive(),
    ],
    'a running one among the expired ones' => [
        'expired',
        fn () => RedirectFactory::new()
            ->expiring(),
    ],
    'an open one among the protected ones' => [
        'protected',
        fn () => RedirectFactory::new(),
    ],
]);

it('leaves a redirect hit lately out of the unused view', function () {
    $redirect = RedirectFactory::createOne();
    recordHits($redirect, 1, time());

    $shown = filteredRedirects($this->seoSpecialist, ['show' => 'unused']);

    expect($shown)->not->toHaveKey($redirect->getId());
});
