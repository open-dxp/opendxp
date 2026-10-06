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
use OpenDxp\Model\Document\Service;
use OpenDxp\Test\Factory\DocumentPageFactory;

beforeEach(fn () => $this->page = DocumentPageFactory::createOne(['prettyUrl' => '/a-pretty-url']));

it('is found under its pretty url', function () {
    $found = Document::getByPath('/a-pretty-url');

    expect($found->getId())->toBe($this->page->getId());
});

it('is not found under its pretty url once it became a link', function () {
    // The row with the pretty url stays behind. Only the type tells the lookup that the document is no page.
    Db::get()->executeStatement(
        'UPDATE documents SET type = ? WHERE id = ?',
        [
            'link',
            $this->page->getId(),
        ],
    );
    RuntimeCache::clear();

    $found = Document::getByPath('/a-pretty-url');

    expect($found)->toBeNull();
});

it('does not count its pretty url as an existing path', function () {
    $exists = Service::pathExists('/a-pretty-url');

    expect($exists)->toBeFalse();
});
