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

namespace OpenDxp\Tests\Feature\HttpCache;

use OpenDxp\HttpCache\HttpCacheTagCollectorInterface;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\DataObjectFolderFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\TestFoundation\Container;
use Symfony\Component\HttpFoundation\Request;

it('tags the response with the document it serves', function () {
    $page = $this->pageServedBy('defaultAction');
    $request = Request::create($page->getFullPath());

    $tags = $this->tagsOf($request);

    expect($tags)->toContain(sprintf('document_%d', $page->getId()));
});

it('tags the response with an element the template loads', function (
    string $attribute,
    ElementInterface $element,
    string $tagPrefix,
) {
    $page = $this->pageServedBy('templateAction');
    $request = Request::create($page->getFullPath());
    $request->attributes->set('_template', 'http_cache/elements.html.twig');
    $request->attributes->set($attribute, $element->getId());

    $tags = $this->tagsOf($request);

    expect($tags)->toContain(sprintf('%s%d', $tagPrefix, $element->getId()));
})->with([
    'a document' => [
        'document_id',
        fn () => DocumentPageFactory::createOne(),
        'document_',
    ],
    'an asset' => [
        'asset_id',
        fn () => AssetImageFactory::createOne(),
        'asset_',
    ],
    'an object' => [
        'object_id',
        fn () => DataObjectFolderFactory::createOne(),
        'data_object_',
    ],
]);

it('tags the response with a listing the controller reads', function (string $action, string $tag) {
    $page = $this->pageServedBy($action);
    $request = Request::create($page->getFullPath());

    $tags = $this->tagsOf($request);

    expect($tags)->toContain($tag);
})->with([
    'a document listing' => ['documentListingAction', 'document_list'],
    'an asset listing' => ['assetListingAction', 'asset_list'],
]);

it('tags the response with a document a sub request loads', function () {
    $page = $this->pageServedBy('templateAction');
    $loaded = DocumentPageFactory::createOne();
    $request = Request::create($page->getFullPath());
    $request->attributes->set('_template', 'http_cache/sub_request.html.twig');
    $request->attributes->set('sub_request_document_id', $loaded->getId());

    $tags = $this->tagsOf($request);

    expect($tags)->toContain(sprintf('document_%d', $loaded->getId()));
});

it('collects no tag outside a request', function () {
    $page = DocumentPageFactory::createOne();

    Document::getById($page->getId(), ['force' => true]);

    expect(Container::get(HttpCacheTagCollectorInterface::class))
        ->isEmpty()
        ->toBeTrue();
});

it('starts over for the next request', function () {
    $first = $this->pageServedBy('defaultAction');
    $second = $this->pageServedBy('defaultAction');
    $this->tagsOf(Request::create($first->getFullPath()));

    $tags = $this->tagsOf(Request::create($second->getFullPath()));

    expect($tags)
        ->toContain(sprintf('document_%d', $second->getId()))
        ->not->toContain(sprintf('document_%d', $first->getId()));
});
