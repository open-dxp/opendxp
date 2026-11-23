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

use OpenDxp\Model\Metadata;
use OpenDxp\Model\Property;
use OpenDxp\Test\Factory\PredefinedAssetMetadataFactory;
use OpenDxp\Test\Factory\PredefinedPropertyFactory;

it('writes a predefined property', function () {
    PredefinedPropertyFactory::createOne(['key' => 'teaser_color']);

    expect(Property\Predefined::getByKey('teaser_color'))
        ->getType()
        ->toBe('text')
        ->getCtype()
        ->toBe('document');
});

it('writes predefined asset metadata', function () {
    PredefinedAssetMetadataFactory::createOne(['name' => 'copyright']);

    expect(Metadata\Predefined::getByName('copyright'))->getType()->toBe('input');
});
