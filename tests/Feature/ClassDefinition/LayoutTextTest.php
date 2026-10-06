<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\ClassDefinition;

use OpenDxp\Model\DataObject\Unittest;
use OpenDxp\Tests\Factory\UnittestFactory;

it('renders the object it belongs to into the text', function () {
    expect(renderedLayoutText('Key: {{ object.key }}'))
        ->toBe('Key: layout-text-object');
});

it('refuses the service container', function () {
    expect(renderedLayoutText('{{ container.getParameter("kernel.environment") }}'))
        ->toContain('Failed rendering the template');
});

it('refuses to delete the object', function () {
    $object = UnittestFactory::createOne();

    expect(renderedLayoutText('{{ object.delete() }}', $object))
        ->toContain('Failed rendering the template')
        ->and(Unittest::getById($object->getId(), ['force' => true]))
        ->not->toBeNull();
});

it('explains a tag the sandbox policy does not allow instead of rendering it', function () {
    expect(renderedLayoutText('{% for i in [1] %}{{ i }}{% endfor %}'))
        ->toContain('Failed rendering the template');
});
