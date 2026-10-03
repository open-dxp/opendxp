<?php

declare(strict_types=1);

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
 * @return list<string> the targets of the automatic redirects away from the source
 */
function autoRedirectTargets(string $source): array
{
    $redirects = new Redirect\Listing();
    $redirects->setCondition('source = ? AND type = ?', [$source, Redirect::TYPE_AUTO_CREATE]);

    return array_map(static fn (Redirect $redirect): string => (string) $redirect->getTarget(), $redirects->load());
}

/**
 * Moves the document into a new folder and lets the listener react as it does after a move in the backend.
 *
 * @return string the path the document had before
 */
function move(Document $document, bool $autoCreateRedirects = true): string
{
    $before = clone $document;
    $formerPath = $document->getRealFullPath();

    $document->setParentId(DocumentFolderFactory::createOne()->getId());
    $document->save();

    (new DocumentListener(['auto_create_redirects' => $autoCreateRedirects]))
        ->onPostMoveAction(new DocumentEvent($document, ['oldDocument' => $before, 'oldPath' => $formerPath]));

    return $formerPath;
}

it('redirects the former path of a moved page, even without a backend user', function () {
    $page = DocumentPageFactory::new()->withParent(DocumentFolderFactory::createOne())->create();

    expect(autoRedirectTargets(move($page)))->toBe([(string) $page->getId()]);
});

it('creates no redirect when automatic redirects are off', function () {
    $page = DocumentPageFactory::new()->withParent(DocumentFolderFactory::createOne())->create();

    expect(autoRedirectTargets(move($page, autoCreateRedirects: false)))->toBe([]);
});

it('creates no redirect when the backend user may not manage redirects', function () {
    Admin::actingAs(UserFactory::createOne(['permissions' => ['documents']]));
    $page = DocumentPageFactory::new()->withParent(DocumentFolderFactory::createOne())->create();

    expect(autoRedirectTargets(move($page)))->toBe([]);
});

it('creates no redirect for the former path of a page with a pretty url', function () {
    $page = DocumentPageFactory::new()->withParent(DocumentFolderFactory::createOne())->create(['prettyUrl' => '/' . uniqid('about-')]);

    expect(autoRedirectTargets(move($page)))->toBe([]);
});

it('redirects the former paths of the subpages, except the ones with a pretty url', function () {
    $parent = DocumentPageFactory::new()->withParent(DocumentFolderFactory::createOne())->create();
    $plain = DocumentPageFactory::new()->withParent($parent)->create();
    $pretty = DocumentPageFactory::new()->withParent($parent)->create(['prettyUrl' => '/' . uniqid('team-')]);
    $plainKey = $plain->getKey();
    $prettyKey = $pretty->getKey();

    $formerPath = move($parent);

    expect(autoRedirectTargets($formerPath . '/' . $plainKey))->toBe([(string) $plain->getId()])
        ->and(autoRedirectTargets($formerPath . '/' . $prettyKey))->toBe([]);
});

it('redirects the former pretty url of a page to its new one', function () {
    $page = DocumentPageFactory::createOne(['prettyUrl' => '/current-pretty']);
    $before = clone $page;
    $before->setPrettyUrl('/former-pretty');

    (new DocumentListener(['auto_create_redirects' => true]))
        ->onPagePostSaveAction(new DocumentEvent($page, ['oldPage' => $before, 'task' => 'publish']));

    expect(autoRedirectTargets('/former-pretty'))->toBe(['/current-pretty']);
});
