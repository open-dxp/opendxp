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

namespace OpenDxp\Tests\Feature\Element;

use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Document;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

// Every installation starts with a root element of each type. A listing counts the root like any other element.
dataset('listings', [
    'assets' => [Asset\Listing::class, AssetImageFactory::class],
    'documents' => [Document\Listing::class, DocumentPageFactory::class],
    'objects' => [DataObject\Listing::class, UnittestFactory::class],
]);

it('counts every element there is', function (string $listing, string $factory) {
    $factory::createMany(5);

    $count = (new $listing())->getTotalCount();

    expect($count)->toBe(6);
})->with('listings');

it('counts no more elements than its limit allows', function (string $listing, string $factory) {
    $factory::createMany(5);
    $elements = new $listing();
    $elements->setLimit(3);
    $elements->setOffset(1);

    $count = $elements->getCount();

    expect($count)->toBe(3);
})->with('listings');

it('counts the elements behind its offset', function (string $listing, string $factory) {
    $factory::createMany(5);
    $elements = new $listing();
    $elements->setLimit(10);
    $elements->setOffset(1);

    $count = $elements->getCount();

    expect($count)->toBe(5);
})->with('listings');

it('counts the same once it was loaded', function (string $listing, string $factory) {
    $factory::createMany(5);
    $elements = new $listing();
    $elements->setLimit(10);
    $elements->setOffset(1);

    $elements->load();

    expect($elements->getCount())
        ->toBe(5)
        ->and($elements->getTotalCount())
        ->toBe(6);
})->with('listings');
