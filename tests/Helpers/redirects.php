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

use OpenDxp\Bundle\SeoBundle\EventListener\DocumentListener;
use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use OpenDxp\Db;
use OpenDxp\Event\Model\DocumentEvent;
use OpenDxp\Model\Document;
use OpenDxp\Model\User;
use OpenDxp\Test\Factory\DocumentFolderFactory;
use OpenDxp\TestFoundation\Browser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Browser\KernelBrowser;

/**
 * Sends what the redirect grid of the admin sends.
 *
 * @param array<string, mixed>|null $data
 * @param array<string, string>     $parameters
 */
function redirectGrid(
    User $user,
    ?string $action = null,
    ?array $data = null,
    array $parameters = [],
): KernelBrowser {
    $body = $data === null
        ? $parameters
        : ['data' => json_encode($data), ...$parameters];

    $url = '/admin/bundle/seo/redirects/list';

    if ($action !== null) {
        $url .= '?xaction=' . $action;
    }

    return Browser::actingAs($user)->post($url, [
        'body' => $body,
    ]);
}

/**
 * @return array<string, mixed>
 */
function gridResponse(KernelBrowser $browser): array
{
    return json_decode($browser->content(), true);
}

/**
 * @param array<string, string> $parameters
 *
 * @return array<int, array<string, mixed>> the listed redirects by ID
 */
function listedRedirects(User $user, array $parameters = []): array
{
    $browser = redirectGrid($user, parameters: [
        'limit' => '1000',
        ...$parameters,
    ]);

    return array_column(gridResponse($browser->assertSuccessful())['data'], null, 'id');
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
        'files' => [
            'redirects' => new UploadedFile($file, 'redirects.csv', 'text/csv', null, true),
        ],
    ]);

    return json_decode($browser->assertSuccessful()->content(), true)['data'];
}

/**
 * Moves the document into a new folder and lets the listener react as it does after a move in the backend.
 *
 * @return string the path the document had before
 */
function moveDocument(Document $document, bool $autoCreateRedirects = true): string
{
    $before = clone $document;
    $formerPath = $document->getRealFullPath();

    $document->setParentId(DocumentFolderFactory::createOne()->getId());
    $document->save();

    $listener = new DocumentListener([
        'auto_create_redirects' => $autoCreateRedirects,
    ]);

    $listener->onPostMoveAction(new DocumentEvent($document, [
        'oldDocument' => $before,
        'oldPath' => $formerPath,
    ]));

    return $formerPath;
}

/**
 * @return list<string> the targets of the automatic redirects away from the source
 */
function autoRedirectTargets(string $source): array
{
    $redirects = new Redirect\Listing();
    $redirects->setCondition('source = ? AND type = ?', [
        $source,
        Redirect::TYPE_AUTO_CREATE,
    ]);

    return array_map(
        static fn (Redirect $redirect): string => (string) $redirect->getTarget(),
        $redirects->load(),
    );
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

function recordHits(Redirect $redirect, int $hits, int $lastHit): void
{
    Db::get()->insert('redirect_hits', [
        'redirectId' => $redirect->getId(),
        'hits' => $hits,
        'lastHit' => $lastHit,
    ]);
}
