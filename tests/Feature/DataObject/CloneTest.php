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

use OpenDxp\Model\DataObject\Service;
use OpenDxp\Tests\Factory\UnittestFactory;

it('keeps a deep copy apart from a change to the original', function () {
    $object = UnittestFactory::new()
        ->withLocalizedValues(
            'linput',
            ['en' => 'original'],
        )
        ->unsaved()
        ->create();
    $copy = Service::cloneMe($object);

    $object->setLinput('changed', 'en');

    expect($copy->getLinput('en'))->toBe('original');
});
