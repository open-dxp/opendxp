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
use OpenDxp\Test\Elements;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Tests\Factory\TestLinksFactory;
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

    $object = TestLinksFactory::createOne([
        'testlink' => $link,
        'ltestlink' => $link,
    ]);
    $loaded = Elements::refresh($object);

    expect($loaded->getTestlink()->getDirect())
        ->toBe('https://www.opendxp.io/')
        ->and($loaded->getLtestlink()->getDirect())
        ->toBe('https://www.opendxp.io/');
});

describe('a link to a deleted asset', function () {
    beforeEach(function () {
        $asset = AssetImageFactory::createOne();
        $this->link = linkToAsset($asset);
        $object = TestLinksFactory::createOne(['testlink' => $this->link]);
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
    $object = TestLinksFactory::new()
        ->unsaved()
        ->create();

    expect(fn () => $object->setTestlink('https://www.opendxp.io/'))
        ->toThrow(TypeError::class, 'must be of type ?OpenDxp\\Model\\DataObject\\Data\\Link, string given')
        ->and(fn () => $object->setLtestlink('https://www.opendxp.io/'))
        ->toThrow(TypeError::class, 'must be of type ?OpenDxp\\Model\\DataObject\\Data\\Link, string given');
});

it('escapes the url and the attributes of a link', function () {
    $link = new Link();
    $link->setDirect('https://example.com/" data-x="1');
    $link->setTitle('Say "hello"');
    $link->setText('Example');

    $html = $link->getHtml();

    expect($html)->toBe(
        '<a href="https://example.com/&quot; data-x=&quot;1" title="Say &quot;hello&quot;">Example</a>',
    );
});

it('drops an event handler from the free attributes of a link', function () {
    $link = new Link();
    $link->setDirect('https://example.com/');
    $link->setAttributes('data-id="1" onclick="track()" download @click=\'open\' ONMOUSEOVER=x');
    $link->setText('Example');

    $html = $link->getHtml();

    expect($html)->toBe('<a href="https://example.com/" data-id="1" download @click="open">Example</a>');
});

it('renders no link for a url that runs a script', function () {
    $link = new Link();
    $link->setDirect('javascript:alert(1)');
    $link->setText('Click');

    $html = $link->getHtml();

    expect($html)->toBe('');
});
