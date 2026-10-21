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

namespace OpenDxp\Tests\Feature\SiteDeclaration;

use OpenDxp\Bundle\SeoBundle\SiteDeclaration\SiteDeclarationRegistry;
use OpenDxp\Bundle\SeoBundle\SiteDeclaration\SiteDeclarationRepository;
use OpenDxp\Model\User;
use OpenDxp\Test\Factory\SiteFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Browser;
use OpenDxp\TestFoundation\Container;
use Zenstruck\Browser\KernelBrowser;

/**
 * @param array<int, string> $contents
 */
function saveDeclaration(User $user, string $name, array $contents): KernelBrowser
{
    return Browser::actingAs($user)
        ->put(sprintf('/admin/bundle/seo/site-declaration?name=%s', $name), [
            'body' => [
                'data' => $contents,
            ],
        ]);
}

/**
 * @return array<string, mixed>
 */
function declarationResponse(KernelBrowser $browser): array
{
    return json_decode(
        $browser->content(),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

function savedContent(string $name, int $siteId): string
{
    $declaration = Container::get(SiteDeclarationRegistry::class)->getDeclaration($name);

    return Container::get(SiteDeclarationRepository::class)->loadContent($declaration, $siteId);
}

beforeEach(function () {
    $this->operator = UserFactory::new()
        ->withPermissions('site_declarations')
        ->create();
});

it('loads the content and the sites for a user without the permission for documents', function () {
    $site = SiteFactory::createOne(['mainDomain' => 'shop.test']);

    $this->saveContent('security_txt', $site->getId(), 'Contact: mailto:security@shop.test');

    $browser = Browser::actingAs($this->operator)->get('/admin/bundle/seo/site-declaration?name=security_txt');

    expect(declarationResponse($browser))
        ->toHaveKey('data', [$site->getId() => 'Contact: mailto:security@shop.test'])
        ->and(array_column(declarationResponse($browser)['sites'], 'domain', 'id'))
        ->toHaveKey($site->getId(), 'shop.test');
});

it('saves valid content and empty content', function () {
    saveDeclaration($this->operator, 'security_txt', [
        0 => "Contact: mailto:security@example.test\nExpires: 2020-01-01T00:00:00Z",
        1 => '',
    ]);

    expect(savedContent('security_txt', 0))
        ->toBe("Contact: mailto:security@example.test\nExpires: 2020-01-01T00:00:00Z")
        ->and(savedContent('security_txt', 1))
        ->toBe('');
});

it('refuses content that breaks a rule of the declaration', function (string $content, array $errors) {
    $browser = saveDeclaration($this->operator, 'security_txt', [0 => $content]);

    expect(declarationResponse($browser))
        ->toHaveKey('success', false)
        ->toHaveKey('errors', [0 => $errors])
        ->and(savedContent('security_txt', 0))
        ->toBe('');
})->with([
    'without Contact' => [
        'Expires: %expires%',
        ['site_declaration_security_txt_contact_missing'],
    ],
    'without Expires' => [
        'Contact: mailto:security@example.test',
        ['site_declaration_security_txt_expires_missing'],
    ],
    'with Expires twice' => [
        "Contact: mailto:security@example.test\nExpires: %expires%\nexpires: %expires%",
        ['site_declaration_security_txt_expires_repeated'],
    ],
]);

it('refuses a user without the permission for site declarations', function () {
    $user = UserFactory::new()
        ->withPermissions('documents')
        ->create();

    Browser::actingAs($user)
        ->get('/admin/bundle/seo/site-declaration?name=security_txt')
        ->assertStatus(403);
});

it('answers 404 for a declaration that is not enabled', function (string $name) {
    $admin = UserFactory::new()
        ->admin()
        ->create();

    Browser::actingAs($admin)
        ->get(sprintf('/admin/bundle/seo/site-declaration?name=%s', $name))
        ->assertStatus(404);
})->with([
    'a disabled declaration' => ['llms_txt'],
    'an unknown name' => ['humans_txt'],
]);
