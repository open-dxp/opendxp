<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\Seo;

use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use OpenDxp\Model\User;
use OpenDxp\Test\Factory\RedirectFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Browser;

beforeEach(function () {
    $this->editor = UserFactory::new()
        ->withPermissions('redirects')
        ->create();
    $this->seoSpecialist = UserFactory::new()
        ->withPermissions('redirects', 'redirects_protected')
        ->create();
});

it('shows protected redirects only to users who may manage them', function () {
    $open = RedirectFactory::createOne([
        'source' => '/open-' . uniqid(),
    ]);
    $protected = RedirectFactory::createOne([
        'source' => '/protected-' . uniqid(),
        'protected' => true,
    ]);
    $admin = UserFactory::new()
        ->admin()
        ->create();

    expect(listedRedirects($this->editor))
        ->toHaveKey($open->getId())
        ->not->toHaveKey($protected->getId())
        ->and(listedRedirects($this->seoSpecialist))
        ->toHaveKeys([$open->getId(), $protected->getId()])
        ->and(listedRedirects($admin))
        ->toHaveKeys([$open->getId(), $protected->getId()]);
});

it('finds no protected redirect for an editor who tests a URL', function () {

    RedirectFactory::createOne([
        'source' => '/tested',
        'protected' => true,
    ]);

    nextRequest();

    expect(listedRedirects($this->editor, ['filter' => 'http://localhost/tested']))
        ->toBe([]);
});

it('keeps an editor from changing or deleting a protected redirect', function (string $action) {
    $protected = RedirectFactory::createOne([
        'source' => '/kept',
        'target' => '/kept-target',
        'protected' => true,
    ]);

    redirectGrid($this->editor, $action, [
        'id' => $protected->getId(),
        'target' => '/changed',
    ])->assertStatus(403);

    expect(Redirect::getById($protected->getId()))
        ->getTarget()
        ->toBe('/kept-target');
})->with([
    'update',
    'destroy',
]);

it('keeps an editor from taking over the source of a protected redirect', function () {
    RedirectFactory::createOne([
        'source' => '/relaunch',
        'target' => '/secret-target',
        'protected' => true,
    ]);

    $browser = redirectGrid($this->editor, 'create', [
        'type' => Redirect::TYPE_PATH,
        'source' => '/Relaunch',
        'target' => '/mine',
        'statusCode' => 301,
        'priority' => 99,
        'active' => true,
    ]);

    expect(gridResponse($browser->assertSuccessful()))
        ->success
        ->toBeFalse()
        ->errors
        ->toBe([
            ['field' => 'source', 'message' => 'redirect_source_protected'],
        ])
        ->and($browser->content())
        ->not->toContain('secret-target');
});

it('keeps an editor from protecting a redirect', function () {
    $browser = redirectGrid($this->editor, 'create', [
        'type' => Redirect::TYPE_PATH,
        'source' => '/editor-' . uniqid(),
        'target' => '/x',
        'statusCode' => 301,
        'priority' => 1,
        'active' => true,
        'protected' => true,
    ]);
    $redirect = Redirect::getById(gridResponse($browser->assertSuccessful())['data']['id']);

    expect($redirect->isProtected())
        ->toBeFalse();
});

it('lets a user who may manage protected redirects protect one', function () {
    $browser = redirectGrid($this->seoSpecialist, 'create', [
        'type' => Redirect::TYPE_PATH,
        'source' => '/specialist-' . uniqid(),
        'target' => '/x',
        'statusCode' => 301,
        'priority' => 1,
        'active' => true,
        'protected' => true,
    ]);
    $redirect = Redirect::getById(gridResponse($browser->assertSuccessful())['data']['id']);

    expect($redirect->isProtected())
        ->toBeTrue();
});

it('saves only the fields an editor may set', function () {
    $browser = redirectGrid($this->editor, 'create', [
        'type' => Redirect::TYPE_PATH,
        'source' => '/fields-' . uniqid(),
        'target' => '/x',
        'statusCode' => 301,
        'priority' => 1,
        'active' => true,
        'userOwner' => 4711,
        'creationDate' => 1,
    ]);
    $redirect = Redirect::getById(gridResponse($browser->assertSuccessful())['data']['id']);

    expect($redirect->getUserOwner())
        ->not->toBe(4711)
        ->and($redirect->getCreationDate())
        ->not->toBe(1);
});

it('refuses a redirect that cannot work', function (array $values, string $field, string $message) {
    $browser = redirectGrid($this->seoSpecialist, 'create', [
        'type' => Redirect::TYPE_PATH,
        'statusCode' => 301,
        'priority' => 1,
        'active' => true,
        ...$values,
    ]);

    expect(gridResponse($browser->assertSuccessful()))
        ->success
        ->toBeFalse()
        ->errors
        ->toContain(['field' => $field, 'message' => $message]);
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

    expect(gridResponse($browser->assertSuccessful()))
        ->success
        ->toBeFalse()
        ->errors
        ->toContain(['field' => 'source', 'message' => 'redirect_source_missing']);
});

it('gives a new redirect the default type, status code and priority for the fields the grid leaves empty', function () {
    $browser = redirectGrid($this->seoSpecialist, 'create', [
        'type' => null,
        'source' => '/old',
        'target' => '/new',
        'statusCode' => null,
        'priority' => null,
    ]);
    $redirect = Redirect::getById(gridResponse($browser->assertSuccessful())['data']['id']);

    expect($redirect)
        ->getType()
        ->toBe(Redirect::TYPE_PATH)
        ->getStatusCode()
        ->toBe(301)
        ->getPriority()
        ->toBe(1);
});

it('saves a start and an expiry that the grid sends as text', function () {
    $redirect = RedirectFactory::createOne([
        'source' => '/dated-' . uniqid(),
        'target' => '/x',
    ]);

    redirectGrid($this->seoSpecialist, 'update', [
        'id' => $redirect->getId(),
        'validFrom' => '1791410400',
        'expiry' => '1791496800',
    ])->assertSuccessful();

    expect(Redirect::getById($redirect->getId()))
        ->getValidFrom()
        ->toBe(1791410400)
        ->getExpiry()
        ->toBe(1791496800);
});

it('warns about a duplicate source and a chain, and saves anyway', function () {
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

    expect(gridResponse($browser->assertSuccessful()))
        ->warnings
        ->toBe([
            ['message' => 'redirect_source_duplicate', 'parameters' => ['%id%' => $duplicate->getId()]],
            ['message' => 'redirect_chain', 'parameters' => ['%id%' => $next->getId()]],
        ]);
});

it('exports protected redirects only to users who may manage them', function () {
    $protected = RedirectFactory::createOne([
        'source' => '/exported-' . uniqid(),
        'protected' => true,
    ]);

    $export = static fn (User $user): string => Browser::actingAs($user)
        ->visit('/admin/bundle/seo/redirects/csv-export')
        ->assertSuccessful()
        ->content();

    expect($export($this->editor))
        ->not->toContain($protected->getSource())
        ->and($export($this->seoSpecialist))
        ->toContain($protected->getSource());
});

it('keeps an editor from importing a protected redirect', function () {
    $result = importRedirects($this->editor, [
        'id;type;source;sourceSite;target;targetSite;statusCode;priority;regex;passThroughParameters;active;expiry;protected',
        ';path;/imported-protected;;/x;;301;1;0;0;1;;1',
        ';path;/imported-open;;/x;;301;1;0;0;1;;0',
    ]);

    expect($result)
        ->toMatchArray([
            'created' => 1,
            'errored' => 1,
        ]);
});

it('imports a file of an older export without the new columns', function () {
    $result = importRedirects($this->seoSpecialist, [
        'id;type;source;sourceSite;target;targetSite;statusCode;priority;regex;passThroughParameters;active;expiry',
        ';path;/older-export;;/x;;301;1;0;0;1;',
    ]);

    expect($result)
        ->toMatchArray([
            'created' => 1,
            'errored' => 0,
        ]);
});

it('lists how often each redirect was hit and when it was last', function () {
    $hit = RedirectFactory::createOne([
        'source' => '/hit-' . uniqid(),
    ]);
    $unhit = RedirectFactory::createOne([
        'source' => '/unhit-' . uniqid(),
    ]);
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
    $few = RedirectFactory::createOne([
        'source' => '/few-' . uniqid(),
    ]);
    $many = RedirectFactory::createOne([
        'source' => '/many-' . uniqid(),
    ]);
    recordHits($few, 1, time());
    recordHits($many, 100, time());

    $ids = array_keys(listedRedirects($this->editor, [
        'sort' => json_encode([
            ['property' => 'hits', 'direction' => 'DESC'],
        ]),
    ]));

    expect(array_search($many->getId(), $ids, true))
        ->toBeLessThan(array_search($few->getId(), $ids, true));
});

it('shows a redirect in the views it belongs to', function (string $view, array $values) {
    $redirect = RedirectFactory::createOne([
        'source' => '/view-' . uniqid(),
        ...$values,
    ]);

    expect(listedRedirects($this->seoSpecialist, ['show' => $view]))
        ->toHaveKey($redirect->getId());
})->with([
    'an active one among the active ones' => ['active', []],
    'an inactive one among the inactive ones' => ['inactive', ['active' => false]],
    'an expired one among the expired ones' => ['expired', ['expiry' => time() - 60]],
    'one that starts later among the scheduled ones' => ['scheduled', ['validFrom' => time() + 3600]],
    'a protected one among the protected ones' => ['protected', ['protected' => true]],
    'one that was never hit among the unused ones' => ['unused', []],
]);

it('leaves a redirect out of the views it does not belong to', function (string $view, array $values) {
    $redirect = RedirectFactory::createOne([
        'source' => '/view-' . uniqid(),
        ...$values,
    ]);

    expect(listedRedirects($this->seoSpecialist, ['show' => $view]))
        ->not->toHaveKey($redirect->getId());
})->with([
    'an inactive one among the active ones' => ['active', ['active' => false]],
    'a running one among the expired ones' => ['expired', ['expiry' => time() + 3600]],
    'an open one among the protected ones' => ['protected', []],
]);

it('hides a redirect hit lately from the unused view', function () {
    $redirect = RedirectFactory::createOne([
        'source' => '/used-' . uniqid(),
    ]);
    recordHits($redirect, 1, time());

    expect(listedRedirects($this->seoSpecialist, ['show' => 'unused']))
        ->not->toHaveKey($redirect->getId());
});
