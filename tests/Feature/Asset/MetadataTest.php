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

it('returns the entry of the language asked for', function (string $language, string $expected) {
    $this->asset->addMetadata('alt', 'input', 'without a language');
    $this->asset->addMetadata('alt', 'input', 'in german', 'de');
    $this->asset->addMetadata('alt', 'input', 'in english', 'en');
    $this->asset->save();

    $found = $this->asset->getMetadata('alt', $language);

    expect($found)->toBe($expected);
})->with([
    'german' => ['de', 'in german'],
    'english' => ['en', 'in english'],
]);

it('falls back to the entry without a language', function () {
    $this->asset->addMetadata('alt', 'input', 'without a language');
    $this->asset->addMetadata('alt', 'input', 'in german', 'de');
    $this->asset->save();

    $found = $this->asset->getMetadata('alt', 'fr');

    expect($found)->toBe('without a language');
});

it('returns the entry of the default language when no language is named', function () {
    $this->asset->addMetadata('alt', 'input', 'in english', 'en');
    $this->asset->addMetadata('alt', 'input', 'in german', 'de');
    $this->asset->save();

    $found = $this->asset->getMetadata('alt');

    expect($found)->toBe('in english');
});

it('returns the entry without a language when none has the default language', function () {
    $this->asset->addMetadata('alt', 'input', 'without a language');
    $this->asset->save();

    $found = $this->asset->getMetadata('alt');

    expect($found)->toBe('without a language');
});

it('returns no fallback on a strict language match', function () {
    $this->asset->addMetadata('alt', 'input', 'without a language');
    $this->asset->addMetadata('alt', 'input', 'in german', 'de');
    $this->asset->save();

    $found = $this->asset->getMetadata('alt', 'fr', strictMatchLanguage: true);

    expect($found)->toBeNull();
});

it('returns nothing for a name without an entry', function () {
    $this->asset->addMetadata('alt', 'input', 'without a language');
    $this->asset->save();

    $found = $this->asset->getMetadata('title', 'de');

    expect($found)->toBeNull();
});

it('returns nothing when neither the language nor a fallback is there', function () {
    $this->asset->addMetadata('alt', 'input', 'in german', 'de');
    $this->asset->save();

    $found = $this->asset->getMetadata('alt', 'fr');

    expect($found)->toBeNull();
});

it('returns the name, data, type and language of a raw entry', function () {
    $this->asset->addMetadata('alt', 'input', 'without a language');
    $this->asset->save();

    $found = $this->asset->getMetadata('alt', 'de', raw: true);

    expect($found)->toMatchArray([
        'name' => 'alt',
        'data' => 'without a language',
        'language' => null,
        'type' => 'input',
    ]);
});

it('returns nothing for a strict raw match that is not there', function () {
    $this->asset->addMetadata('alt', 'input', 'without a language');
    $this->asset->save();

    $found = $this->asset->getMetadata('alt', 'de', strictMatchLanguage: true, raw: true);

    expect($found)->toBeNull();
});

it('returns every entry when nothing is asked for', function () {
    $this->asset->addMetadata('alt', 'input', 'in english', 'en');
    $this->asset->addMetadata('alt', 'input', 'in german', 'de');
    $this->asset->addMetadata('alt', 'input', 'without a language');
    $this->asset->addMetadata('title', 'input', 'a title');
    $this->asset->save();

    $found = $this->asset->getMetadata();

    expect($found)->toHaveCount(4);
});

it('returns only the entries of one language on a strict raw request', function () {
    $this->asset->addMetadata('alt', 'input', 'in english', 'en');
    $this->asset->addMetadata('alt', 'input', 'in german', 'de');
    $this->asset->addMetadata('alt', 'input', 'without a language');
    $this->asset->save();

    $found = $this->asset->getMetadata(language: 'en', strictMatchLanguage: true, raw: true);

    expect(array_column($found, 'language'))->toBe(['en']);
});

it('falls back per name to the entry without a language', function () {
    $this->asset->addMetadata('alt', 'input', 'in german', 'de');
    $this->asset->addMetadata('title', 'input', 'a title');
    $this->asset->save();

    $found = $this->asset->getMetadata(language: 'de', raw: true);

    expect(array_column($found, 'language'))->toEqualCanonicalizing([
        'de',
        null,
    ]);
});
