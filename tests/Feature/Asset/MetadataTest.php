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

use OpenDxp\Test\Factory\AssetImageFactory;

beforeEach(function () {
    $this->asset = AssetImageFactory::createOne();
});

it('hands back the entry of the language that was asked for', function () {

    $this->asset->addMetadata('alt', 'input', 'without a language', null);
    $this->asset->addMetadata('alt', 'input', 'in german', 'de');
    $this->asset->addMetadata('alt', 'input', 'in english', 'en');
    $this->asset->save();

    expect($this->asset->getMetadata('alt', 'de'))
        ->toBe('in german')
        ->and($this->asset->getMetadata('alt', 'en'))
        ->toBe('in english');
});

it('falls back to the entry without a language', function (string $language) {

    $this->asset->addMetadata('alt', 'input', 'without a language', null);
    $this->asset->addMetadata('alt', 'input', 'in german', 'de');
    $this->asset->save();

    expect($this->asset->getMetadata('alt', $language))->toBe('without a language');
})->with(['it', 'fr']);

it('hands back the entry of the default language when no language is named', function () {

    $this->asset->addMetadata('alt', 'input', 'in english', 'en');
    $this->asset->addMetadata('alt', 'input', 'in german', 'de');
    $this->asset->save();

    expect($this->asset->getMetadata('alt'))->toBe('in english');
});

it('hands back the entry without a language when none carries the default one', function () {

    $this->asset->addMetadata('alt', 'input', 'without a language');
    $this->asset->save();

    expect($this->asset->getMetadata('alt'))->toBe('without a language');
});

it('insists on the exact language when a strict match is asked for', function () {

    $this->asset->addMetadata('alt', 'input', 'without a language', null);
    $this->asset->addMetadata('alt', 'input', 'in german', 'de');
    $this->asset->save();

    expect($this->asset->getMetadata('alt', 'de', true))
        ->toBe('in german')
        ->and($this->asset->getMetadata('alt', 'fr', true))
        ->toBeNull();
});

it('hands back nothing for a name it holds no entry under', function () {

    $this->asset->addMetadata('alt', 'input', 'without a language', null);
    $this->asset->save();

    expect($this->asset->getMetadata('title', 'de'))->toBeNull();
});

it('hands back nothing when neither the language nor a fallback is there', function () {

    $this->asset->addMetadata('alt', 'input', 'in german', 'de');
    $this->asset->save();

    expect($this->asset->getMetadata('alt', 'fr'))->toBeNull();
});

it('names the data, the type and the language of a raw entry', function () {

    $this->asset->addMetadata('alt', 'input', 'without a language');
    $this->asset->save();

    expect($this->asset->getMetadata('alt', 'de', false, true))->toMatchArray([
        'name' => 'alt',
        'data' => 'without a language',
        'language' => null,
        'type' => 'input',
    ]);
});

it('hands back nothing for a strict raw match that is not there', function () {

    $this->asset->addMetadata('alt', 'input', 'without a language');
    $this->asset->save();

    expect($this->asset->getMetadata('alt', 'de', true, true))->toBeNull();
});

it('hands back every entry it holds when nothing is asked for', function () {

    foreach (['en', 'de', null] as $language) {
        foreach (['one', 'two', 'three'] as $name) {
            $this->asset->addMetadata($name, 'input', $name, $language);
        }
    }

    $this->asset->addMetadata('four', 'input', 'four');
    $this->asset->save();

    expect($this->asset->getMetadata())->toHaveCount(10);
});

it('hands back only the entries of one language on a strict raw request', function () {

    foreach (['en', 'de', null] as $language) {
        $this->asset->addMetadata('alt', 'input', 'alt', $language);
    }

    $this->asset->save();

    $found = $this->asset->getMetadata(null, 'en', true, true);

    expect($found)
        ->toHaveCount(1)
        ->and(array_column($found, 'language'))
        ->toBe(['en']);
});

it('stands in with an entry without a language for a name that has none in that language', function () {

    $this->asset->addMetadata('alt', 'input', 'alt', 'de');
    $this->asset->addMetadata('title', 'input', 'title');
    $this->asset->save();

    $found = $this->asset->getMetadata(null, 'de', false, true);

    expect($found)
        ->toHaveCount(2)
        ->and(array_column($found, 'language'))
        ->toEqualCanonicalizing(['de', null]);
});
