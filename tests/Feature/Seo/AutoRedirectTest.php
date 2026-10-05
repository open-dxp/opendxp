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

use OpenDxp\Bundle\SeoBundle\EventListener\DocumentListener;
use OpenDxp\Event\Model\DocumentEvent;
use OpenDxp\Test\Factory\DocumentFolderFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Admin;

it('redirects the former path of a moved page, even without a backend user', function () {
    $page = DocumentPageFactory::new()
        ->withParent(DocumentFolderFactory::createOne())
        ->create();

    expect(autoRedirectTargets(moveDocument($page)))
        ->toBe([(string) $page->getId()]);
});

it('creates no redirect when automatic redirects are off', function () {
    $page = DocumentPageFactory::new()
        ->withParent(DocumentFolderFactory::createOne())
        ->create();

    expect(autoRedirectTargets(moveDocument($page, autoCreateRedirects: false)))
        ->toBe([]);
});

it('creates no redirect when the backend user may not manage redirects', function () {
    Admin::actingAs(UserFactory::new()
        ->withPermissions('documents')
        ->create());
    $page = DocumentPageFactory::new()
        ->withParent(DocumentFolderFactory::createOne())
        ->create();

    expect(autoRedirectTargets(moveDocument($page)))
        ->toBe([]);
});

it('creates no redirect for the former path of a page with a pretty url', function () {
    $page = DocumentPageFactory::new()
        ->withParent(DocumentFolderFactory::createOne())
        ->create([
            'prettyUrl' => '/' . uniqid('about-'),
        ]);

    expect(autoRedirectTargets(moveDocument($page)))
        ->toBe([]);
});

it('redirects the former paths of the subpages, except the ones with a pretty url', function () {
    $parent = DocumentPageFactory::new()
        ->withParent(DocumentFolderFactory::createOne())
        ->create();
    $plain = DocumentPageFactory::new()
        ->withParent($parent)
        ->create();
    $pretty = DocumentPageFactory::new()
        ->withParent($parent)
        ->create([
            'prettyUrl' => '/' . uniqid('team-'),
        ]);
    $plainKey = $plain->getKey();
    $prettyKey = $pretty->getKey();

    $formerPath = moveDocument($parent);

    expect(autoRedirectTargets($formerPath . '/' . $plainKey))
        ->toBe([(string) $plain->getId()])
        ->and(autoRedirectTargets($formerPath . '/' . $prettyKey))
        ->toBe([]);
});

it('redirects the former pretty url of a page to its new one', function () {
    $page = DocumentPageFactory::createOne([
        'prettyUrl' => '/current-pretty',
    ]);
    $before = clone $page;
    $before->setPrettyUrl('/former-pretty');

    $listener = new DocumentListener([
        'auto_create_redirects' => true,
    ]);
    $listener->onPagePostSaveAction(new DocumentEvent($page, [
        'oldPage' => $before,
        'task' => 'publish',
    ]));

    expect(autoRedirectTargets('/former-pretty'))
        ->toBe(['/current-pretty']);
});
