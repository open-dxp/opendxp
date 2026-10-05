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


namespace OpenDxp\Tests\Feature\Document;

use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Db;
use OpenDxp\Model\Document;
use OpenDxp\Model\Document\Page;
use OpenDxp\Model\Document\Service;
use OpenDxp\Test\Factory\DocumentPageFactory;

beforeEach(function () {
    $this->url = '/pretty-url-' . uniqid();
    $this->page = DocumentPageFactory::createOne();
    $this->page->setPrettyUrl($this->url);
    $this->page->save();
});

it('is found under its pretty url', function () {
    expect(Document::getByPath($this->url))->toBeInstanceOf(Page::class);
});

it('is no longer found under its pretty url once it became another type', function () {

    Db::get()->executeStatement('UPDATE documents SET type = ? WHERE id = ?', ['link', $this->page->getId()]);
    RuntimeCache::clear();

    expect(Document::getByPath($this->url))->toBeNull();
});

it('does not count a pretty url as a path that exists', function () {
    expect(Service::pathExists($this->url))->toBeFalse();
});
