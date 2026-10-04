<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Application\Areabrick;

use OpenDxp\Extension\Document\Areabrick\AbstractTemplateAreabrick;

final class Greeting extends AbstractTemplateAreabrick
{
    public function getName(): string
    {
        return 'Greeting';
    }
}
