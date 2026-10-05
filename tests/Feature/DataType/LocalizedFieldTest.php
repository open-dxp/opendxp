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

namespace OpenDxp\Tests\Feature\DataType;

use Closure;
use OpenDxp\Model\DataObject\Unittest;
use OpenDxp\Tests\Factory\TestObjectFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

// A relation field needs something to point at, and the dataset reads those back out of the database.
beforeEach(fn () => TestObjectFactory::createMany(6));

it('keeps a localized field apart per language', function (string $field, Closure $value) {

    $object = UnittestFactory::new()->unsaved()->create();
    $setter = 'set' . ucfirst($field);

    foreach (['de', 'en'] as $language) {
        $object->{$setter}($value($language), $language);
    }

    $object->save();
    $written = Unittest::getById($object->getId(), ['force' => true]);

    foreach (['de', 'en'] as $language) {
        expect($written)->toCarryField($field, $value($language), $language);
    }
})->with('localized field values');

it('tells two texts apart that only look like the same number', function () {

    $object = UnittestFactory::new()->unsaved()->create();
    $object->setLinput('0001', 'en');
    $object->setLinput('0.1000', 'de');
    $object->save();

    $written = Unittest::getById($object->getId(), ['force' => true]);
    $definition = $written->getClass()->getFieldDefinition('localizedfields')->getFieldDefinition('linput');

    expect($definition->isEqual($written->getLinput('en'), '000001'))
        ->toBeFalse()
        ->and($definition->isEqual($written->getLinput('de'), '0.100000'))
        ->toBeFalse();
});
