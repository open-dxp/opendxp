<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\Factory;

use OpenDxp\Model\Translation;
use OpenDxp\Test\Factory\TranslationFactory;

it('writes a translated key', function () {

    $translation = TranslationFactory::new()
        ->withTranslations(['en' => 'Read more', 'de' => 'Mehr erfahren'])
        ->create(['key' => 'teaser.more']);

    expect($translation)
        ->toBeInstanceOf(Translation::class)
        ->and(Translation::getByKey('teaser.more')->getTranslation('de'))
        ->toBe('Mehr erfahren');
});
