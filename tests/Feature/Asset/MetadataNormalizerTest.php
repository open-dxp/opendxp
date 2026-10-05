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


namespace OpenDxp\Tests\Feature\Asset;

use OpenDxp\Model\Asset;
use OpenDxp\Model\Asset\Metadata\Loader\DataLoader;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\TestFoundation\Container;
use OpenDxp\Tests\Factory\UnittestFactory;

beforeEach(function () {
    $this->asset = AssetImageFactory::createOne();
    $this->loader = Container::get('opendxp.implementation_loader.asset.metadata.data');
});

it('hands a metadata entry back unchanged after a round trip through the normalizer', function (string $type, callable $value) {

    $original = $value();
    $this->asset->addMetadata('metadata', $type, $original);
    $this->asset->save();

    $reloaded = Asset::getById($this->asset->getId(), ['force' => true]);
    $normalizer = $this->loader->build($reloaded->getMetadata('metadata', null, false, true)['type']);

    $stored = $normalizer->normalize($reloaded->getMetadata('metadata'));

    expect($normalizer->denormalize($stored))->toEqual($original);
})->with([
    'an asset' => ['asset', fn () => AssetImageFactory::createOne()],
    'a document' => ['document', fn () => DocumentPageFactory::createOne()],
    'an object' => ['object', fn () => UnittestFactory::createOne()],
    'a line of text' => ['input', fn () => 'foo bar'],
    'several lines of text' => ['textarea', fn () => "foo bar\nsecond line"],
    'a date' => ['date', fn () => time()],
    'a checkbox that is ticked' => ['checkbox', fn () => true],
    'a checkbox that is not' => ['checkbox', fn () => false],
    'a selected option' => ['select', fn () => 'somevalue'],
]);
