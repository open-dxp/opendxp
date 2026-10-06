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

use OpenDxp\DataObject\Consent\Service;
use OpenDxp\Model\DataObject\Data\Consent;
use OpenDxp\TestFoundation\Container;
use OpenDxp\Tests\Factory\UnittestFactory;

beforeEach(function () {
    $this->object = UnittestFactory::createOne();
    $this->service = Container::get(Service::class);
});

it('keeps a given consent with its note', function () {
    $this->service->giveConsent($this->object, 'consent', 'some consent content');

    $loaded = reloaded($this->object);

    expect($loaded)
        ->toCarryField('consent', new Consent(true))
        ->and($loaded->getConsent()->getNote()->getDescription())
        ->toBe('some consent content');
});

it('holds no consent once it is revoked', function () {
    $this->service->giveConsent($this->object, 'consent', 'some consent content');

    $this->service->revokeConsent($this->object, 'consent');

    expect(reloaded($this->object)->getConsent()->getConsent())->toBeFalse();
});
