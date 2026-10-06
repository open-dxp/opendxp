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

namespace OpenDxp\Tests\Feature\Document;

use Exception;
use OpenDxp\Model\Document\Editable\Input;
use OpenDxp\Test\Factory\DocumentPageFactory;

beforeEach(function () {
    $this->main = DocumentPageFactory::new()
        ->withEditables(['headline' => (new Input())->setDataFromResource('test')])
        ->create();
});

it('inherits no editable from its parent alone', function () {
    $child = DocumentPageFactory::new()
        ->withParent($this->main)
        ->create();

    $headline = reloaded($child)->getEditable('headline');

    expect($headline)->toBeNull();
});

it('takes the editables of its content main document', function () {
    $page = DocumentPageFactory::new()
        ->withContentMainDocument($this->main)
        ->create();

    $headline = reloaded($page)->getEditable('headline');

    expect($headline->getValue())->toBe('test');
});

it('refuses a content main document that already points back at it', function () {
    $follower = DocumentPageFactory::new()
        ->withContentMainDocument($this->main)
        ->create();

    expect(fn () => $this->main->setContentMainDocumentId($follower->getId(), validate: true))
        ->toThrow(
            Exception::class,
            'This document is already part of the main document chain, please choose a different one.',
        );
});
