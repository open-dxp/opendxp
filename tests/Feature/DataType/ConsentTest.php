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
use OpenDxp\Model\DataObject\Unittest;
use OpenDxp\TestFoundation\Container;
use OpenDxp\Tests\Factory\UnittestFactory;

beforeEach(function () {
    $this->object = UnittestFactory::createOne();
    $this->service = Container::get(Service::class);
});

it('keeps the consent that was given, with the note it was given for', function () {

    $this->service->giveConsent($this->object, 'consent', 'some consent content');

    $given = Unittest::getById($this->object->getId(), ['force' => true])->getConsent();

    expect($given->getConsent())
        ->toBeTrue()
        ->and($given->getNote()->getDescription())
        ->toBe('some consent content');

    expect(Unittest::getById($this->object->getId(), ['force' => true]))
        ->toCarryField('consent', new Consent(true));
});

it('holds no consent any more once it was revoked', function () {

    $this->service->giveConsent($this->object, 'consent', 'some consent content');
    $this->service->revokeConsent($this->object, 'consent');

    expect(Unittest::getById($this->object->getId(), ['force' => true])->getConsent()->getConsent())
        ->toBeFalse();
});
