<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Application\Areabrick;

use OpenDxp\Extension\Document\Areabrick\AbstractTemplateAreabrick;

final class Box extends AbstractTemplateAreabrick
{
    public function getName(): string
    {
        return 'Box';
    }
}
