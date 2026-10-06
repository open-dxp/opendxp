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

use OpenDxp\Db;
use OpenDxp\Model\Translation;
use OpenDxp\Test\Factory\TranslationFactory;
use OpenDxp\TestFoundation\Container;
use OpenDxp\Tests\Story\Translations;
use Symfony\Contracts\Translation\TranslatorInterface;

function messagesListing(): Translation\Listing
{
    $listing = new Translation\Listing();
    $listing->setDomain('messages');

    return $listing;
}

/**
 * @param array<Translation> $translations
 *
 * @return list<string>
 */
function translationKeys(array $translations): array
{
    return array_map(
        static fn (Translation $translation): string => $translation->getKey(),
        $translations,
    );
}

function storedText(Translation $translation, string $language): string
{
    return Db::get()->fetchOne(
        'SELECT `text` FROM translations_messages WHERE `key` = ? AND `language` = ?',
        [
            $translation->getKey(),
            $language,
        ],
    );
}

beforeEach(function () {
    Translations::load();
    $this->translator = Container::get(TranslatorInterface::class);
});

it('translates a key into the language that was asked for', function (string $language, string $expected) {
    $this->translator->setLocale($language);

    $translated = $this->translator->trans('simple_key');

    expect($translated)->toBe($expected);
})->with([
    'english' => ['en', 'EN Text'],
    'german' => ['de', 'DE Text'],
    'french' => ['fr', 'FR Text'],
]);

it('falls back to english when the german translation is blank', function () {
    $this->translator->setLocale('de');

    $translated = $this->translator->trans('fallback_key');

    expect($translated)->toBe('EN Fallback');
});

it('translates a key that is a text itself', function (string $language) {
    $this->translator->setLocale($language);

    $translated = $this->translator->trans('Text As Key');

    expect($translated)->toBe('EN Text');
})->with([
    'in english' => ['en'],
    'in german, through the fallback' => ['de'],
]);

it('puts the parameters into the translation', function (string $language) {
    $this->translator->setLocale($language);

    $translated = $this->translator->trans('text_params', [
        '%Param1%' => 'First Parameter',
        '%Param2%' => 'Second Parameter',
    ]);

    expect($translated)->toBe('Text with First Parameter and Second Parameter');
})->with([
    'in english' => ['en'],
    'in german, through the fallback' => ['de'],
]);

it('puts a count into the translation', function (string $language) {
    $this->translator->setLocale($language);

    $translated = $this->translator->trans('count_key', ['%count%' => 2]);

    expect($translated)->toBe('2 Count');
})->with([
    'in english' => ['en'],
    'in german, through the fallback' => ['de'],
]);

it('puts a count into a translation longer than 190 characters', function () {
    $this->translator->setLocale('en');
    $text = Translations::longText()->getTranslation('en');

    $translated = $this->translator->trans('count_key_190', ['%count%' => 192]);

    expect($translated)->toBe(strtr($text, ['%count%' => '192']));
});

it('picks the plural form that fits the count', function (int $count, string $expected) {
    $this->translator->setLocale('en');

    $translated = $this->translator->trans('count_plural_1|count_plural_n', ['%count%' => $count]);

    expect($translated)->toBe($expected);
})->with([
    'one of them' => [1, '1 Item'],
    'several of them' => [5, '5 Items'],
]);

it('tells an upper case key from a lower case one', function (string $key, string $expected) {
    $this->translator->setLocale('en');

    $translated = $this->translator->trans($key);

    expect($translated)->toBe($expected);
})->with([
    'lower case' => ['case_key', 'Lower Case Key'],
    'upper case' => ['CASE_KEY', 'Upper Case Key'],
]);

it('lists every translation of a domain', function () {
    $listing = messagesListing();

    $found = $listing->getTranslations();

    expect(translationKeys($found))->toEqualCanonicalizing([
        'simple_key',
        'fallback_key',
        'Text As Key',
        'text_params',
        'count_key',
        'count_key_190',
        'count_plural_1',
        'count_plural_n',
        'case_key',
        'CASE_KEY',
    ]);
});

it('lists only the translations a condition parameter matches', function () {
    $listing = messagesListing();
    $listing->addConditionParam('`key` like :key', ['key' => 'simple%']);

    $found = $listing->getTranslations();

    expect(translationKeys($found))->toBe(['simple_key']);
});

it('leaves out the languages that were not asked for', function () {
    $listing = messagesListing();
    $listing->setCondition('`key` = ?', ['simple_key']);
    $listing->setLanguages([
        'en',
        'de',
    ]);

    $found = $listing->getTranslations();

    expect($found[0]->getTranslations())->toEqual([
        'en' => 'EN Text',
        'de' => 'DE Text',
    ]);
});

it('applies its own condition, not that of an earlier listing', function () {
    $earlier = messagesListing();
    $earlier->setCondition('`key` LIKE ?', ['simple%']);
    $earlier->getTranslations();
    $listing = messagesListing();
    $listing->setCondition('`key` LIKE ?', ['fallback%']);

    $found = $listing->getTranslations();

    expect(translationKeys($found))->toBe(['fallback_key']);
});

it('sees a translation that was saved after it loaded once', function () {
    $listing = messagesListing();
    $listing->load();
    $translation = TranslationFactory::new()
        ->withTranslations(['en' => 'test'])
        ->create();

    $found = $listing->load();

    expect(translationKeys($found))->toContain($translation->getKey());
});

it('stores a translation without its script and with its special characters encoded', function () {
    $translation = TranslationFactory::new()
        ->withTranslations(['en' => '!@#$%^abc\'"<script>console.log("ops");</script> 测试&lt; edf &gt; "'])
        ->create();

    expect(storedText($translation, 'en'))->toBe('!&#64;#$%^abc&#039;&#34; 测试&lt; edf &gt; &#34;');
});
