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


namespace OpenDxp\Tests\Feature\Inheritance;

use OpenDxp;
use OpenDxp\Model\DataObject\Service;
use OpenDxp\Tests\Factory\InheritanceFactory;

beforeEach(function () {
    // Only the admin is handed an object that holds nothing of its own.
    OpenDxp::setAdminMode();
    $this->group = storeGroup('testgroup2')->getId();
    $this->first = storeKey('input')->getId();
    $this->second = storeKey('textarea')->getId();
});

it('hands a key down that the object below holds no value for', function () {

    Service::useInheritedValues(true, function () {

        $parent = InheritanceFactory::createOne();
        $above = $parent->getTeststore();
        $above->setLocalizedKeyValue($this->group, $this->first, 'first of the parent');
        $above->setLocalizedKeyValue($this->group, $this->second, 'second of the parent');
        $parent->save();

        $child = InheritanceFactory::createOne(['parentId' => $parent->getId()]);
        $below = $child->getTeststore();
        $below->setLocalizedKeyValue($this->group, $this->first, 'first of the child');
        $below->save();

        expect($below->getLocalizedKeyValue($this->group, $this->first))
            ->toBe('first of the child')
            ->and($below->getLocalizedKeyValue($this->group, $this->second))
            ->toBe('second of the parent')
            ->and($above->getLocalizedKeyValue($this->group, $this->first))
            ->toBe('first of the parent')
            ->and($above->getLocalizedKeyValue($this->group, $this->second))
            ->toBe('second of the parent');
    });
});
