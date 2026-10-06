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

use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\TestFoundation\Container;
use OpenDxp\Tests\Factory\UnittestFactory;

beforeEach(function () {
    $this->asset = AssetImageFactory::createOne();
    $this->loader = Container::get('opendxp.implementation_loader.asset.metadata.data');
});

it('keeps a stored metadata value through a round trip of the normalizer', function (string $type, mixed $value) {
    $this->asset->addMetadata('metadata', $type, $value);
    $this->asset->save();
    $stored = reloaded($this->asset)->getMetadata('metadata');
    $normalizer = $this->loader->build($type);

    $normalized = $normalizer->normalize($stored);
    $denormalized = $normalizer->denormalize($normalized);

    expect($denormalized)->toBe($stored);
})->with([
    'a line of text' => ['input', 'foo bar'],
    'several lines of text' => ['textarea', "foo bar\nsecond line"],
    'a date' => ['date', 1714978089],
    'a ticked checkbox' => ['checkbox', true],
    'an empty checkbox' => ['checkbox', false],
    'a selected option' => ['select', 'somevalue'],
]);

it('keeps a stored metadata element through a round trip of the normalizer', function (
    string $type,
    ElementInterface $element,
) {
    $this->asset->addMetadata('metadata', $type, $element);
    $this->asset->save();
    $stored = reloaded($this->asset)->getMetadata('metadata');
    $normalizer = $this->loader->build($type);

    $normalized = $normalizer->normalize($stored);
    $denormalized = $normalizer->denormalize($normalized);

    expect($denormalized)
        ->toBeInstanceOf($element::class)
        ->getId()
        ->toBe($element->getId());
})->with([
    'an asset' => [
        'asset',
        fn () => AssetImageFactory::createOne(),
    ],
    'a document' => [
        'document',
        fn () => DocumentPageFactory::createOne(),
    ],
    'an object' => [
        'object',
        fn () => UnittestFactory::createOne(),
    ],
]);
