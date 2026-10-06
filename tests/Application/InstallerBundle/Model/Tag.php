<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Application\InstallerBundle\Model;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'installer_bundle_tag')]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;
}
