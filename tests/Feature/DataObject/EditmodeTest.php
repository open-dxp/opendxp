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

it('reads nothing out of an empty field the backend sent', function (string $type, mixed $sent) {

    $object = UnittestFactory::createOne();

    expect((new $type())->getDataFromEditmode($sent, $object))->toBeNull();
})->with([
    'a line of text' => [Data\Input::class],
    'several lines of text' => [Data\Textarea::class],
    'formatted text' => [Data\Wysiwyg::class],
    'a password' => [Data\Password::class],
])->with([
    'left empty' => [''],
    'left out' => [null],
]);

it('reads nothing out of an empty quantity the backend sent', function (mixed $value) {

    $object = UnittestFactory::createOne();

    expect((new Data\InputQuantityValue())->getDataFromEditmode(['value' => $value, 'unit' => $value], $object))
        ->toBeNull();
})->with([
    'left empty' => [''],
    'left out' => [null],
]);
