<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\Seo;

use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use OpenDxp\Model\User;
use OpenDxp\Test\Factory\RedirectFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Browser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Browser\KernelBrowser;

function editor(): User
{
    return UserFactory::createOne(['permissions' => ['redirects']]);
}

function seoSpecialist(): User
{
    return UserFactory::createOne(['permissions' => ['redirects', 'redirects_protected']]);
}

/**
 * Sends what the redirect grid of the admin sends.
 *
 * @param array<string, mixed>|null $data
 * @param array<string, string>     $parameters
 */
function redirectGrid(User $user, ?string $action = null, ?array $data = null, array $parameters = []): KernelBrowser
{
    $body = $data === null ? $parameters : ['data' => json_encode($data), ...$parameters];

    return Browser::actingAs($user)->post('/admin/bundle/seo/redirects/list' . ($action ? '?xaction=' . $action : ''), ['body' => $body]);
}

/**
 * @return list<int>
 */
function listedRedirects(User $user, string $filter = ''): array
{
    $response = json_decode(redirectGrid($user, parameters: ['filter' => $filter, 'limit' => '1000'])->assertSuccessful()->content(), true);

    return array_map(static fn (array $redirect): int => $redirect['id'], $response['data']);
}

/**
 * @param list<string> $lines
 *
 * @return array<string, mixed> the statistics of the import
 */
function importRedirects(User $user, array $lines): array
{
    $file = tempnam(sys_get_temp_dir(), 'redirects');
    file_put_contents($file, implode("\n", $lines));

    $browser = Browser::actingAs($user)->post('/admin/bundle/seo/redirects/csv-import', [
        'files' => ['redirects' => new UploadedFile($file, 'redirects.csv', 'text/csv', null, true)],
    ]);

    return json_decode($browser->assertSuccessful()->content(), true)['data'];
}

/**
 * @return array<string, mixed>
 */
function gridResponse(KernelBrowser $browser): array
{
    return json_decode($browser->content(), true);
}

it('shows protected redirects only to users who may manage them', function () {
    $open = RedirectFactory::createOne(['source' => '/open-' . uniqid()]);
    $protected = RedirectFactory::createOne(['source' => '/protected-' . uniqid(), 'protected' => true]);

    expect(listedRedirects(editor()))->toContain($open->getId())->not->toContain($protected->getId())
        ->and(listedRedirects(seoSpecialist()))->toContain($open->getId(), $protected->getId())
        ->and(listedRedirects(UserFactory::new()->admin()->create()))->toContain($open->getId(), $protected->getId());
});

it('finds no protected redirect for an editor who tests a URL', function () {
    RedirectFactory::createOne(['source' => '/tested', 'protected' => true]);
    nextRequest();

    expect(listedRedirects(editor(), 'http://localhost/tested'))->toBe([]);
});

it('keeps an editor from changing or deleting a protected redirect', function (string $action) {
    $protected = RedirectFactory::createOne(['source' => '/kept', 'target' => '/kept-target', 'protected' => true]);

    redirectGrid(editor(), $action, ['id' => $protected->getId(), 'target' => '/changed'])->assertStatus(403);

    expect(Redirect::getById($protected->getId()))->getTarget()->toBe('/kept-target');
})->with(['update', 'destroy']);

it('keeps an editor from taking over the source of a protected redirect', function () {
    RedirectFactory::createOne(['source' => '/relaunch', 'target' => '/secret-target', 'protected' => true]);

    $browser = redirectGrid(editor(), 'create', ['type' => Redirect::TYPE_PATH, 'source' => '/Relaunch', 'target' => '/mine', 'statusCode' => 301, 'priority' => 99, 'active' => true]);

    $browser->assertStatus(422);
    expect(gridResponse($browser)['errors'])->toBe([['field' => 'source', 'message' => 'redirect_source_protected']])
        ->and($browser->content())->not->toContain('secret-target');
});

it('keeps an editor from protecting a redirect', function () {
    $browser = redirectGrid(editor(), 'create', ['type' => Redirect::TYPE_PATH, 'source' => '/editor-' . uniqid(), 'target' => '/x', 'statusCode' => 301, 'priority' => 1, 'active' => true, 'protected' => true]);

    expect(Redirect::getById(gridResponse($browser->assertSuccessful())['data']['id']))->isProtected()->toBeFalse();
});

it('lets a user who may manage protected redirects protect one', function () {
    $browser = redirectGrid(seoSpecialist(), 'create', ['type' => Redirect::TYPE_PATH, 'source' => '/specialist-' . uniqid(), 'target' => '/x', 'statusCode' => 301, 'priority' => 1, 'active' => true, 'protected' => true]);

    expect(Redirect::getById(gridResponse($browser->assertSuccessful())['data']['id']))->isProtected()->toBeTrue();
});

it('saves only the fields an editor may set', function () {
    $browser = redirectGrid(editor(), 'create', ['type' => Redirect::TYPE_PATH, 'source' => '/fields-' . uniqid(), 'target' => '/x', 'statusCode' => 301, 'priority' => 1, 'active' => true, 'userOwner' => 4711, 'creationDate' => 1]);

    $redirect = Redirect::getById(gridResponse($browser->assertSuccessful())['data']['id']);
    expect($redirect->getUserOwner())->not->toBe(4711)
        ->and($redirect->getCreationDate())->not->toBe(1);
});

it('refuses a redirect that cannot work', function (array $values, string $field, string $message) {
    $browser = redirectGrid(seoSpecialist(), 'create', ['type' => Redirect::TYPE_PATH, 'statusCode' => 301, 'priority' => 1, 'active' => true, ...$values]);

    $browser->assertStatus(422);
    expect(gridResponse($browser)['errors'])->toContain(['field' => $field, 'message' => $message]);
})->with([
    'an invalid regular expression' => [['source' => '@^/broken(@', 'target' => '/x', 'regex' => true], 'source', 'redirect_regex_invalid'],
    'a redirect to itself' => [['source' => '/circle', 'target' => '/Circle'], 'target', 'redirect_loop'],
    'a domain with a path' => [['type' => Redirect::TYPE_DOMAIN, 'source' => 'example.test/path', 'target' => 'https://x.test/'], 'source', 'redirect_domain_source_invalid'],
    'a domain to a relative target' => [['type' => Redirect::TYPE_DOMAIN, 'source' => 'event.test', 'target' => '/landing'], 'target', 'redirect_domain_target_relative'],
]);

it('warns about a duplicate source and a chain, and saves anyway', function () {
    $duplicate = RedirectFactory::createOne(['source' => '/sale', 'target' => '/shop']);
    $next = RedirectFactory::createOne(['source' => '/summer', 'target' => '/beach']);

    $browser = redirectGrid(seoSpecialist(), 'create', ['type' => Redirect::TYPE_PATH, 'source' => '/sale', 'target' => '/summer', 'statusCode' => 302, 'priority' => 5, 'active' => true]);

    expect(gridResponse($browser->assertSuccessful())['warnings'])->toBe([
        ['message' => 'redirect_source_duplicate', 'parameters' => ['%id%' => $duplicate->getId()]],
        ['message' => 'redirect_chain', 'parameters' => ['%id%' => $next->getId()]],
    ]);
});

it('exports protected redirects only to users who may manage them', function () {
    $protected = RedirectFactory::createOne(['source' => '/exported-' . uniqid(), 'protected' => true]);

    $export = static fn (User $user): string => Browser::actingAs($user)->visit('/admin/bundle/seo/redirects/csv-export')->assertSuccessful()->content();

    expect($export(editor()))->not->toContain($protected->getSource())
        ->and($export(seoSpecialist()))->toContain($protected->getSource());
});

it('keeps an editor from importing a protected redirect', function () {
    $result = importRedirects(editor(), [
        'id;type;source;sourceSite;target;targetSite;statusCode;priority;regex;passThroughParameters;active;expiry;protected',
        ';path;/imported-protected;;/x;;301;1;0;0;1;;1',
        ';path;/imported-open;;/x;;301;1;0;0;1;;0',
    ]);

    expect($result)->toMatchArray(['created' => 1, 'errored' => 1]);
});

it('imports a file of an older export without the new columns', function () {
    $result = importRedirects(seoSpecialist(), [
        'id;type;source;sourceSite;target;targetSite;statusCode;priority;regex;passThroughParameters;active;expiry',
        ';path;/older-export;;/x;;301;1;0;0;1;',
    ]);

    expect($result)->toMatchArray(['created' => 1, 'errored' => 0]);
});
