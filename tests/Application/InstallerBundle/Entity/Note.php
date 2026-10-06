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

namespace OpenDxp\Tests\Application\InstallerBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OpenDxp\Tests\Application\InstallerBundle\Model\Tag;

#[ORM\Entity]
#[ORM\Table(name: 'installer_bundle_note')]
class Note
{
    #[ORM\Column]
    #[ORM\GeneratedValue]
    #[ORM\Id]
    public ?int $id = null;

    #[ORM\Column(length: 190)]
    public string $title = '';

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\JoinTable(name: 'installer_bundle_note_tag')]
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    public Collection $tags;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }
}
