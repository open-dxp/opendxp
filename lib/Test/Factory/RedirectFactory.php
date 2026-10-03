<?php

declare(strict_types=1);

namespace OpenDxp\Test\Factory;

use OpenDxp\Bundle\SeoBundle\Model\Redirect;

/**
 * @extends AbstractSavingFactory<Redirect>
 */
final class RedirectFactory extends AbstractSavingFactory
{
    public static function class(): string
    {
        return Redirect::class;
    }

    protected function defaults(): array
    {
        return [
            'type'       => Redirect::TYPE_PATH,
            'source'     => sprintf('/redirect-%s', uniqid()),
            'target'     => '/',
            'statusCode' => 301,
            'priority'   => 1,
            'active'     => true,
        ];
    }
}
