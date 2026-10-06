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

use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\Data\Link;
use OpenDxp\Model\Element\ValidationException;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Tests\Factory\UnittestLinkFactory;
use TypeError;

function linkToAsset(Asset $asset): Link
{
    $link = new Link();
    $link->setInternal($asset->getId());
    $link->setInternalType('asset');

    return $link;
}

it('keeps the url of a link, localized or not', function () {
    $link = new Link();
    $link->setDirect('https://www.opendxp.io/');

    $object = UnittestLinkFactory::createOne([
        'testlink' => $link,
        'ltestlink' => $link,
    ]);
    $loaded = reloaded($object);

    expect($loaded->getTestlink()->getDirect())
        ->toBe('https://www.opendxp.io/')
        ->and($loaded->getLtestlink()->getDirect())
        ->toBe('https://www.opendxp.io/');
});

describe('a link to a deleted asset', function () {
    beforeEach(function () {
        $asset = AssetImageFactory::createOne();
        $this->link = linkToAsset($asset);
        $object = UnittestLinkFactory::createOne(['testlink' => $this->link]);
        $this->definition = $object->getClass()->getFieldDefinition('testlink');

        $asset->delete();
    });

    it('is invalid', function () {
        $message = sprintf(
            'invalid internal link, referenced document with id [%d] does not exist',
            $this->link->getInternal(),
        );

        expect(fn () => $this->definition->checkValidity($this->link))->toThrow(ValidationException::class, $message);
    });

    it('is emptied when invalid fields are reset', function () {
        $this->definition->checkValidity(
            $this->link,
            omitMandatoryCheck: true,
            params: ['resetInvalidFields' => true],
        );

        expect($this->link)
            ->getInternal()
            ->toBeNull()
            ->getInternalType()
            ->toBeNull();
    });
});

it('refuses a plain url where a link belongs', function () {
    $object = UnittestLinkFactory::new()
        ->unsaved()
        ->create();

    expect(fn () => $object->setTestlink('https://www.opendxp.io/'))
        ->toThrow(TypeError::class, 'must be of type ?OpenDxp\\Model\\DataObject\\Data\\Link, string given')
        ->and(fn () => $object->setLtestlink('https://www.opendxp.io/'))
        ->toThrow(TypeError::class, 'must be of type ?OpenDxp\\Model\\DataObject\\Data\\Link, string given');
});
