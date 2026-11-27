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

namespace OpenDxp\Tests\Feature\Xliff;

use OpenDxp\Bundle\XliffBundle\AttributeSet\Attribute;
use OpenDxp\Bundle\XliffBundle\AttributeSet\AttributeSet;
use OpenDxp\Bundle\XliffBundle\ImporterService\Importer\DataObjectImporter;
use OpenDxp\Bundle\XliffBundle\TranslationItemCollection\TranslationItem;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Test\Elements;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Browser;
use OpenDxp\Tests\Factory\TestFieldTypesFactory;

function importLocalizedField(Concrete $object, string $name, string $content): void
{
    $attributeSet = (new AttributeSet(new TranslationItem('object', (string) $object->getId(), $object)))
        ->setSourceLanguage('en')
        ->setTargetLanguages(['de'])
        ->addAttribute(Attribute::TYPE_LOCALIZED_FIELD, $name, $content);

    (new DataObjectImporter())->import($attributeSet);
}

it('imports localized field of xliff file', function () {
    $object = TestFieldTypesFactory::new()->create();

    importLocalizedField($object, 'linput', 'Deutscher Text');

    expect(Elements::refresh($object))
        ->toCarryLocalizedField('linput', 'Deutscher Text', 'de');
});

it('ignores xliff attribute without localized field definition', function () {
    $object = TestFieldTypesFactory::new()->create();
    $key = $object->getKey();

    importLocalizedField($object, 'key', 'renamed');

    expect(Elements::refresh($object)->getKey())->toBe($key);
});

it('rejects xliff file id outside temp directory', function (string $method, string $url) {
    $admin = UserFactory::new()
        ->admin()
        ->create();

    Browser::actingAs($admin)
        ->request($method, sprintf('%s?id=%s', $url, rawurlencode('../escape')), [
            'body' => [
                'id' => '../escape',
            ],
        ])
        ->assertStatus(400);
})->with([
    'export' => ['POST', '/admin/bundle/xliff/translation/xliff-export'],
    'download' => ['GET', '/admin/bundle/xliff/translation/xliff-export-download'],
    'import' => ['POST', '/admin/bundle/xliff/translation/xliff-import-element'],
]);
