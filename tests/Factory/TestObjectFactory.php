<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Factory;

use OpenDxp\Model\DataObject\TestObject;
use OpenDxp\Test\Factory\AbstractDataObjectFactory;

/**
 * @extends AbstractDataObjectFactory<TestObject>
 */
final class TestObjectFactory extends AbstractDataObjectFactory
{
    public static function class(): string
    {
        return TestObject::class;
    }
}
