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

use OpenDxp\Model\Document;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\DataObjectFolderFactory;
use Symfony\Component\HttpFoundation\Request;

it('names the document a request reached', function () {

    $page = $this->taggedPage();

    expect($this->tagsOf(Request::create($page->getFullPath())))->toContain('document_' . $page->getId());
});

it('names a document the template loads', function () {

    $page = $this->taggedPage();
    $loaded = $this->taggedPage();

    $request = Request::create($page->getFullPath());
    $request->attributes->set('_template', 'test/tag_collection.html.twig');
    $request->attributes->set('test_doc_id', $loaded->getId());

    expect($this->tagsOf($request))
        ->toContain('document_' . $page->getId())
        ->toContain('document_' . $loaded->getId());
});

it('names an asset the template loads', function () {

    $page = $this->taggedPage();
    $asset = AssetImageFactory::createOne();

    $request = Request::create($page->getFullPath());
    $request->attributes->set('_template', 'test/tag_collection.html.twig');
    $request->attributes->set('test_asset_id', $asset->getId());

    expect($this->tagsOf($request))
        ->toContain('document_' . $page->getId())
        ->toContain('asset_' . $asset->getId());
});

it('names an object the template loads', function () {

    $page = $this->taggedPage();
    $folder = DataObjectFolderFactory::createOne();

    $request = Request::create($page->getFullPath());
    $request->attributes->set('_template', 'test/tag_collection.html.twig');
    $request->attributes->set('test_obj_id', $folder->getId());

    expect($this->tagsOf($request))->toContain('data_object_' . $folder->getId());
});

it('names every element the template loads', function () {

    $page = $this->taggedPage();
    $document = $this->taggedPage();
    $asset = AssetImageFactory::createOne();

    $request = Request::create($page->getFullPath());
    $request->attributes->set('_template', 'test/tag_collection.html.twig');
    $request->attributes->set('test_doc_id', $document->getId());
    $request->attributes->set('test_asset_id', $asset->getId());

    expect($this->tagsOf($request))
        ->toContain('document_' . $document->getId())
        ->toContain('asset_' . $asset->getId());
});

it('names the document listing when one is read', function () {

    $request = Request::create($this->taggedPage()->getFullPath());
    $request->attributes->set('test_doc_listing', true);

    expect($this->tagsOf($request))->toContain('document_list');
});

it('names the asset listing when one is read', function () {

    $request = Request::create($this->taggedPage()->getFullPath());
    $request->attributes->set('test_asset_listing', true);

    expect($this->tagsOf($request))->toContain('asset_list');
});

it('names an element a sub request loads', function () {

    $page = $this->taggedPage();
    $main = $this->taggedPage();
    $sub = $this->taggedPage();

    $request = Request::create($page->getFullPath());
    $request->attributes->set('_template', 'test/tag_collection_subrequest.html.twig');
    $request->attributes->set('test_doc_id', $main->getId());
    $request->attributes->set('test_sub_doc_id', $sub->getId());

    expect($this->tagsOf($request))
        ->toContain('document_' . $main->getId())
        ->toContain('document_' . $sub->getId());
});

it('collects nothing outside a request', function () {

    $page = $this->taggedPage();

    Document::getById($page->getId(), ['force' => true]);

    expect($this->tagCollector()->isEmpty())->toBeTrue();
});

it('starts over for the next request', function () {

    $first = $this->taggedPage();
    $second = $this->taggedPage();

    $this->tagsOf(Request::create($first->getFullPath()));

    expect($this->tagsOf(Request::create($second->getFullPath())))
        ->toContain('document_' . $second->getId())
        ->not->toContain('document_' . $first->getId());
});
