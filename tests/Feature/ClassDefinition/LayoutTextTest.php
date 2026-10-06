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

namespace OpenDxp\Tests\Feature\ClassDefinition;

use OpenDxp\Model\DataObject\ClassDefinition\Layout\Text;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Tests\Factory\UnittestFactory;

function renderedLayoutText(string $html, Concrete $object): string
{
    $text = new Text();
    $text->setHtml($html);
    $text->enrichLayoutDefinition($object);

    return $text->getHtml();
}

beforeEach(fn () => $this->object = UnittestFactory::createOne());

it('renders the object it belongs to into the text', function () {
    $html = renderedLayoutText('Key: {{ object.key }}', $this->object);

    expect($html)->toBe(sprintf('Key: %s', $this->object->getKey()));
});

it('refuses the service container', function () {
    $html = renderedLayoutText('{{ container.getParameter("kernel.environment") }}', $this->object);

    expect($html)->toContain('Failed rendering the template');
});

it('refuses to delete the object', function () {
    $html = renderedLayoutText('{{ object.delete() }}', $this->object);

    expect($html)
        ->toContain('Failed rendering the template')
        ->and(reloaded($this->object))
        ->not->toBeNull();
});

it('refuses a tag the sandbox policy does not allow', function () {
    $html = renderedLayoutText('{% for i in [1] %}{{ i }}{% endfor %}', $this->object);

    expect($html)->toContain('Failed rendering the template');
});
