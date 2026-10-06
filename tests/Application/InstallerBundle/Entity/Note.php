<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Application\InstallerBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OpenDxp\Tests\Application\InstallerBundle\Model\Tag;

#[ORM\Entity]
#[ORM\Table(name: 'installer_bundle_note')]
class Note
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 190)]
    public string $title = '';

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'installer_bundle_note_tag')]
    public Collection $tags;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }
}
