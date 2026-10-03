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

use OpenDxp\Model\DataObject\Data\Link;
use OpenDxp\Model\DataObject\UnittestLink;
use OpenDxp\Model\Element\ValidationException;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Tests\Factory\LinkObjectFactory;
use TypeError;

function aLinkTo(int $assetId): Link
{
    $link = new Link();
    $link->setInternal($assetId);
    $link->setInternalType('asset');

    return $link;
}

it('hands a link back with the url it was saved with, localized or not', function () {

    $link = new Link();
    $link->setDirect('https://www.opendxp.io/');

    $object = LinkObjectFactory::createOne(['testlink' => $link, 'ltestlink' => $link]);
    $written = UnittestLink::getById($object->getId(), ['force' => true]);

    expect($written->getTestlink()->getDirect())
        ->toBe('https://www.opendxp.io/')
        ->and($written->getLtestlink()->getDirect())
        ->toBe('https://www.opendxp.io/');
});

it('calls a link invalid once the element it points at is gone', function () {

    $asset = AssetImageFactory::createOne();
    $link = aLinkTo($asset->getId());
    $definition = LinkObjectFactory::createOne(['testlink' => $link])
        ->getClass()
        ->getFieldDefinition('testlink');

    $asset->delete();

    expect(fn () => $definition->checkValidity($link))->toThrow(ValidationException::class);
});

it('empties a link that points nowhere instead of calling it invalid, when it is asked to', function () {

    $asset = AssetImageFactory::createOne();
    $link = aLinkTo($asset->getId());
    $definition = LinkObjectFactory::createOne(['testlink' => $link])
        ->getClass()
        ->getFieldDefinition('testlink');

    $asset->delete();
    $definition->checkValidity($link, true, ['resetInvalidFields' => true]);

    expect($link->getInternal())
        ->toBeNull()
        ->and($link->getInternalType())
        ->toBeNull();
});

it('refuses a plain url where a link belongs', function () {

    $object = LinkObjectFactory::new()->unsaved()->create();

    expect(fn () => $object->setTestlink('https://www.opendxp.io/'))->toThrow(TypeError::class);
    expect(fn () => $object->setLtestlink('https://www.opendxp.io/'))->toThrow(TypeError::class);
});
