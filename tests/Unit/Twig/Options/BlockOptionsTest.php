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


namespace OpenDxp\Tests\Unit\Twig\Options;

use OpenDxp\Twig\Options\BlockOptions;

it('writes the options the way a template needs them', function (callable $set, string $expected) {

    $options = new BlockOptions();
    $set($options);

    expect($options->toString())->toBe($expected);
})->with([
    'nothing set' => [
        fn (BlockOptions $options) => null,
        "['manual' => false,'reload' => false,'default' => 0,]",
    ],
    'manual' => [
        fn (BlockOptions $options) => $options->setManual(true),
        "['manual' => true,'reload' => false,'default' => 0,]",
    ],
    'reload' => [
        fn (BlockOptions $options) => $options->setReload(true),
        "['manual' => false,'reload' => true,'default' => 0,]",
    ],
    'a limit' => [
        fn (BlockOptions $options) => $options->setLimit(5),
        "['manual' => false,'limit' => 5,'reload' => false,'default' => 0,]",
    ],
    'a class' => [
        fn (BlockOptions $options) => $options->setClass('my-class'),
        "['manual' => false,'reload' => false,'default' => 0,'class' => \"my-class\",]",
    ],
    'a default' => [
        fn (BlockOptions $options) => $options->setDefault(42),
        "['manual' => false,'reload' => false,'default' => 42,]",
    ],
]);
