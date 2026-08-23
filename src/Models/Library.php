<?php
namespace Naomai\Compactorium\Models;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'libraries')]
class Library {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\Column(name: 'owner_id', type: 'integer')]
    public int $ownerId = 0;

    #[ORM\Column(type: 'string')]
    public string $name;

    #[ORM\Column(type: 'string', nullable: true)]
    public ?string $description = null;

    #[ORM\OneToMany(
        mappedBy: 'library',
        targetEntity: Copy::class
    )]
    public Collection $copies;

    #[ORM\OneToMany(
        mappedBy: 'library',
        targetEntity: Scan::class
    )]
    public Collection $scans;

    public function __construct() {
        $this->copies = new ArrayCollection();
        $this->scans = new ArrayCollection();
    }
}