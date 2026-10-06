<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Application\InstallerBundleExtension\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'installer_bundle_extension_other')]
class Other
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;
}
