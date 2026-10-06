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

namespace OpenDxp\Tests\Feature\DataObject;

use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Tests\Factory\UnittestFactory;

dataset('empty values', [
    'left empty' => '',
    'left out' => null,
]);

it('reads nothing out of an empty field the backend sent', function (Data $definition, ?string $sent) {
    $object = UnittestFactory::createOne();

    $value = $definition->getDataFromEditmode($sent, $object);

    expect($value)->toBeNull();
})->with([
    'a line of text' => fn () => new Data\Input(),
    'several lines of text' => fn () => new Data\Textarea(),
    'formatted text' => fn () => new Data\Wysiwyg(),
    'a password' => fn () => new Data\Password(),
])->with('empty values');

it('reads nothing out of an empty quantity the backend sent', function (?string $sent) {
    $object = UnittestFactory::createOne();
    $definition = new Data\InputQuantityValue();

    $value = $definition->getDataFromEditmode(
        [
            'value' => $sent,
            'unit' => $sent,
        ],
        $object,
    );

    expect($value)->toBeNull();
})->with('empty values');
