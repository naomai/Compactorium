<?php
namespace Naomai\Compactorium\Models;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'barcodes')]
class Barcode {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\Column(type: 'integer')]
    public int $barcode;

    #[ORM\ManyToOne(
        targetEntity: Album::class,
        inversedBy: 'barcodes'
    )]
    #[ORM\JoinColumn(
        name: 'album_slug',
        referencedColumnName: 'slug',
        nullable: false
    )]
    public Album $album;
}