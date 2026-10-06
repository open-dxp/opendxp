<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Story;

use OpenDxp\Model\Translation;
use OpenDxp\Test\Factory\TranslationFactory;
use Zenstruck\Foundry\Story;

/**
 * Ten translations of the domain messages. English is complete, German and French are partly blank.
 *
 * @method static Translation longText()
 */
final class Translations extends Story
{
    public function build(): void
    {
        $this->translate('simple_key', [
            'en' => 'EN Text',
            'de' => 'DE Text',
            'fr' => 'FR Text',
        ]);
        // A blank translation exists. The translator falls back to English for it.
        $this->translate('fallback_key', [
            'en' => 'EN Fallback',
            'de' => '',
        ]);
        $this->translate('Text As Key', [
            'en' => 'EN Text',
            'de' => '',
            'fr' => '',
        ]);
        $this->translate('text_params', [
            'en' => 'Text with %Param1% and %Param2%',
            'de' => '',
        ]);
        $this->translate('count_key', [
            'en' => '%count% Count',
            'de' => '',
        ]);
        $this->translate('count_plural_1', ['en' => '1 Item']);
        $this->translate('count_plural_n', ['en' => '%count% Items']);
        $this->translate('case_key', ['en' => 'Lower Case Key']);
        $this->translate('CASE_KEY', ['en' => 'Upper Case Key']);

        $longText = $this->translate('count_key_190', [
            'en' => 'This is a translated text generated from translator service, using count parameter to be '
                . 'replaced from passed parameters and having %count% characters to test text greater than 190 '
                . 'characters.',
        ]);
        $this->addState('longText', $longText);
    }

    /**
     * @param array<string, string> $byLanguage
     */
    private function translate(string $key, array $byLanguage): Translation
    {
        return TranslationFactory::new()
            ->withTranslations($byLanguage)
            ->create(['key' => $key]);
    }
}
