<?php

declare(strict_types=1);

namespace OpenDxp\Test\Factory;

use OpenDxp\Model\Document\PageSnippet;

/**
 * @template T of PageSnippet
 *
 * @extends AbstractDocumentFactory<T>
 */
abstract class AbstractPageSnippetFactory extends AbstractDocumentFactory
{
    /**
     * @param class-string $controller
     */
    public function withController(string $controller, string $action): static
    {
        return $this->with(['controller' => sprintf('%s::%s', $controller, $action)]);
    }

    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'missingRequiredEditable' => false,
        ];
    }

    protected function initialize(): static
    {
        return parent::initialize()->withNavigationName();
    }
}
