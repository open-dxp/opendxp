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

namespace OpenDxp\Tests\Feature\Factory;

use OpenDxp\Model\Translation;
use OpenDxp\Test\Factory\TranslationFactory;

it('writes a translated key', function () {
    TranslationFactory::new()
        ->withTranslations([
            'en' => 'Read more',
            'de' => 'Mehr erfahren',
        ])
        ->create(['key' => 'teaser.more']);

    expect(Translation::getByKey('teaser.more'))
        ->getTranslation('en')
        ->toBe('Read more')
        ->getTranslation('de')
        ->toBe('Mehr erfahren');
});

it('writes a key into the admin domain', function () {
    $translation = TranslationFactory::new()
        ->inAdminDomain()
        ->withTranslations(['en' => 'Save'])
        ->create();

    $loaded = Translation::getByKey(
        $translation->getKey(),
        Translation::DOMAIN_ADMIN,
    );
    expect($loaded)->getTranslation('en')->toBe('Save');
});
