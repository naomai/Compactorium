<?php
namespace Naomai\Compactorium\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: 'scans',
    uniqueConstraints: [
        new ORM\UniqueConstraint(
            name: 'idx_scans_unique',
            columns: ['barcode', 'library_id']
        )
    ]
)]
class Scan {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public int $id;

    // Temporary until User entity exists.
    #[ORM\Column(name: 'owner_id', type: 'integer')]
    public int $ownerId = 0;

    #[ORM\ManyToOne(
        targetEntity: Library::class,
        inversedBy: 'scans'
        
    )]
    #[ORM\JoinColumn(
        name: 'library_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    public Library $library;

    #[ORM\Column(type: 'string')]
    public string $barcode;

    #[ORM\Column(
        name: 'scanned_at',
        type: 'datetime_immutable'
    )]
    public DateTimeImmutable $scannedAt;

    #[ORM\Column(
        name: 'processed',
        type: 'boolean',
        options: ['default' => false]
    )]
    public bool $processed = false;

    #[ORM\OneToOne(
        targetEntity: Copy::class,
        mappedBy: 'scan',
    )]
    public ?Copy $copy;

    #[ORM\ManyToMany(targetEntity: Barcode::class)]
    #[ORM\JoinTable(name: 'barcodes')]
    #[ORM\JoinColumn(
        name: 'barcode',
        referencedColumnName: 'barcode'
    )]
    #[ORM\InverseJoinColumn(
        name: 'barcode',
        referencedColumnName: 'barcode'
    )]
    public Collection $barcodes;

    public function __construct() {
        $this->barcodes = new ArrayCollection();
    }
}