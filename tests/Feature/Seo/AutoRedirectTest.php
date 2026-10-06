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
use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use OpenDxp\Event\Model\DocumentEvent;
use OpenDxp\Model\Document;
use OpenDxp\Test\Factory\DocumentFolderFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Admin;

/**
 * Moves the document into a new folder and lets the listener react as it does after a move in the backend. The
 * path the document had before is returned.
 */
function moveDocument(Document $document, DocumentListener $listener): string
{
    $before = clone $document;
    $formerPath = $document->getRealFullPath();
    $folder = DocumentFolderFactory::createOne();

    $document->setParentId($folder->getId());
    $document->save();

    $event = new DocumentEvent($document, [
        'oldDocument' => $before,
        'oldPath' => $formerPath,
    ]);
    $listener->onPostMoveAction($event);

    return $formerPath;
}

/**
 * Returns the targets of the automatic redirects away from the source.
 *
 * @return list<?string>
 */
function autoRedirectTargets(string $source): array
{
    $redirects = new Redirect\Listing();
    $redirects->setCondition('source = ? AND type = ?', [
        $source,
        Redirect::TYPE_AUTO_CREATE,
    ]);

    return array_map(
        static fn (Redirect $redirect): ?string => $redirect->getTarget(),
        $redirects->load(),
    );
}

beforeEach(function () {
    $this->listener = new DocumentListener(['auto_create_redirects' => true]);
    $this->folder = DocumentFolderFactory::createOne();
});

it('redirects the former path of a moved page without a backend user', function () {
    $page = DocumentPageFactory::new()
        ->withParent($this->folder)
        ->create();

    $formerPath = moveDocument($page, $this->listener);

    expect(autoRedirectTargets($formerPath))->toBe([(string) $page->getId()]);
});

it('creates no redirect when automatic redirects are off', function () {
    $listener = new DocumentListener(['auto_create_redirects' => false]);
    $page = DocumentPageFactory::new()
        ->withParent($this->folder)
        ->create();

    $formerPath = moveDocument($page, $listener);

    expect(autoRedirectTargets($formerPath))->toBe([]);
});

it('creates no redirect when the backend user may not manage redirects', function () {
    $user = UserFactory::new()
        ->withPermissions('documents')
        ->create();
    Admin::actingAs($user);
    $page = DocumentPageFactory::new()
        ->withParent($this->folder)
        ->create();

    $formerPath = moveDocument($page, $this->listener);

    expect(autoRedirectTargets($formerPath))->toBe([]);
});

it('creates no redirect for the former path of a page with a pretty url', function () {
    $page = DocumentPageFactory::new()
        ->withParent($this->folder)
        ->create(['prettyUrl' => '/about']);

    $formerPath = moveDocument($page, $this->listener);

    expect(autoRedirectTargets($formerPath))->toBe([]);
});

it('redirects the former paths of the child pages except the ones with a pretty url', function () {
    $parent = DocumentPageFactory::new()
        ->withParent($this->folder)
        ->create();
    $plain = DocumentPageFactory::new()
        ->withParent($parent)
        ->create();
    $pretty = DocumentPageFactory::new()
        ->withParent($parent)
        ->create(['prettyUrl' => '/team']);

    $formerPath = moveDocument($parent, $this->listener);

    expect(autoRedirectTargets(sprintf('%s/%s', $formerPath, $plain->getKey())))
        ->toBe([(string) $plain->getId()])
        ->and(autoRedirectTargets(sprintf('%s/%s', $formerPath, $pretty->getKey())))
        ->toBe([]);
});

it('redirects the former pretty url of a page to its new one', function () {
    $page = DocumentPageFactory::createOne(['prettyUrl' => '/current-pretty']);
    $before = clone $page;
    $before->setPrettyUrl('/former-pretty');
    $event = new DocumentEvent($page, [
        'oldPage' => $before,
        'task' => 'publish',
    ]);

    $this->listener->onPagePostSaveAction($event);

    expect(autoRedirectTargets('/former-pretty'))->toBe(['/current-pretty']);
});
