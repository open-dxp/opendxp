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


namespace OpenDxp\Tests\Feature\Translation;

use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Db;
use OpenDxp\Model\Translation;
use OpenDxp\Test\Factory\TranslationFactory;
use OpenDxp\TestFoundation\Container;
use Symfony\Contracts\Translation\TranslatorInterface;

const LONG_TEXT = 'This is a translated text generated from translator service, using count parameter to be replaced from passed parameters and having %count% characters to test text greater than 190 characters.';

// An empty string is a translation that exists and is blank, which is what makes the fallback run.
const TRANSLATIONS = [
    'simple_key' => ['en' => 'EN Text', 'de' => 'DE Text', 'fr' => 'FR Text'],
    'fallback_key' => ['en' => 'EN Fallback', 'de' => ''],
    'Text As Key' => ['en' => 'EN Text', 'de' => '', 'fr' => ''],
    'text_params' => ['en' => 'Text with %Param1% and %Param2%', 'de' => ''],
    'count_key' => ['en' => '%count% Count', 'de' => ''],
    'count_key_190' => ['en' => LONG_TEXT],
    'count_plural_1' => ['en' => '1 Item'],
    'count_plural_n' => ['en' => '%count% Items'],
    'case_key' => ['en' => 'Lower Case Key'],
    'CASE_KEY' => ['en' => 'Upper Case Key'],
];

const PARAMETERS = ['%Param1%' => 'First Parameter', '%Param2%' => 'Second Parameter'];

beforeEach(function () {
    $this->translator = Container::get(TranslatorInterface::class);

    foreach (TRANSLATIONS as $key => $perLanguage) {
        TranslationFactory::new()->with(['key' => $key])->withTranslations($perLanguage)->create();
    }
});

it('translates a key into the language that was asked for', function (string $language, string $expected) {

    $this->translator->setLocale($language);

    expect($this->translator->trans('simple_key'))->toBe($expected);
})->with([
    'english' => ['en', 'EN Text'],
    'german' => ['de', 'DE Text'],
    'french' => ['fr', 'FR Text'],
]);

it('falls back to english when the german translation is blank', function () {

    $this->translator->setLocale('de');

    expect($this->translator->trans('fallback_key'))->toBe('EN Fallback');
});

it('translates a key that is a text itself', function (string $language) {

    $this->translator->setLocale($language);

    expect($this->translator->trans('Text As Key'))->toBe('EN Text');
})->with([
    'in english' => ['en'],
    'in german, through the fallback' => ['de'],
]);

it('puts the parameters into the translation', function (string $language) {

    $this->translator->setLocale($language);

    expect($this->translator->trans('text_params', PARAMETERS))
        ->toBe('Text with First Parameter and Second Parameter');
})->with([
    'in english' => ['en'],
    'in german, through the fallback' => ['de'],
]);

it('puts a count into the translation', function (string $language) {

    $this->translator->setLocale($language);

    expect($this->translator->trans('count_key', ['%count%' => 2]))->toBe('2 Count');
})->with([
    'in english' => ['en'],
    'in german, through the fallback' => ['de'],
]);

it('puts a count into a translation longer than 190 characters', function () {

    $this->translator->setLocale('en');

    expect($this->translator->trans('count_key_190', ['%count%' => 192]))
        ->toBe(strtr(LONG_TEXT, ['%count%' => '192']));
});

it('picks the plural form that fits the count', function (int $count, string $expected) {

    $this->translator->setLocale('en');

    expect($this->translator->trans('count_plural_1|count_plural_n', ['%count%' => $count]))->toBe($expected);
})->with([
    'one of them' => [1, '1 Item'],
    'several of them' => [5, '5 Items'],
]);

it('tells an upper case key from a lower case one', function (string $key, string $expected) {

    $this->translator->setLocale('en');

    expect($this->translator->trans($key))->toBe($expected);
})->with([
    'lower case' => ['case_key', 'Lower Case Key'],
    'upper case' => ['CASE_KEY', 'Upper Case Key'],
]);

it('lists every translation of a domain', function () {

    $listing = new Translation\Listing();
    $listing->setDomain('messages');

    expect($listing->getTranslations())->toHaveCount(count(TRANSLATIONS));
});

it('lists only the translations a condition parameter matches', function () {

    $listing = new Translation\Listing();
    $listing->setDomain('messages');
    $listing->addConditionParam('`key` like :key', ['key' => 'simple%']);

    expect($listing->getTranslations())->toHaveCount(1);
});

it('leaves out the languages that were not asked for', function () {

    $listing = new Translation\Listing();
    $listing->setDomain('messages');
    $listing->setLanguages(['en', 'de']);

    expect($listing->getTranslations()[0]->getTranslations())->not->toHaveKey('fr');
});

it('matches what its own condition says, not what an earlier listing asked for', function (string $condition, string $expected) {

    $listing = new Translation\Listing();
    $listing->setDomain('messages');
    $listing->setCondition('`key` LIKE ?', [$condition]);

    $found = $listing->getTranslations();

    expect($found)
        ->toHaveCount(1)
        ->and($found[0]->getKey())
        ->toBe($expected);
})->with([
    'the simple key' => ['simple%', 'simple_key'],
    'the fallback key' => ['fallback%', 'fallback_key'],
]);

it('sees a translation that was saved after it had loaded once', function () {

    $listing = new Translation\Listing();
    $listing->setDomain('messages');
    $before = count($listing->load());

    TranslationFactory::new()->with(['key' => 'test'])->withTranslations(['en' => 'test'])->create();

    expect($listing->load())->toHaveCount($before + 1);
});

it('strips the markup from a translation before it is stored', function () {

    $translation = TranslationFactory::new()
        ->with(['key' => 'sanitizerTest'])
        ->withTranslations(['en' => '!@#$%^abc\'"<script>console.log("ops");</script> 测试&lt; edf &gt; "'])
        ->create();

    RuntimeCache::clear();
    $expected = '!@#$%^abc\'" 测试< edf > "';

    $stored = Db::get()->fetchOne(
        'SELECT `text` FROM translations_messages WHERE `key` = ? AND `language` = ?',
        [$translation->getKey(), 'en'],
    );

    expect(html_entity_decode(Translation::getByKey('sanitizerTest')->getTranslation('en')))
        ->toBe($expected)
        ->and(html_entity_decode($stored))
        ->toBe($expected);
});
