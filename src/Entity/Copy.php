<?php
namespace Naomai\Compactorium\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'copies')]
class Copy {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\ManyToOne(
        targetEntity: Library::class,
        inversedBy: 'copies',
    )]
    #[ORM\JoinColumn(
        name: 'library_id', 
        referencedColumnName: 'id', 
        nullable: false,
    )]
    public Library $library;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', nullable: false)]
    public User $owner;

    #[ORM\OneToOne(
        targetEntity: Scan::class,
        inversedBy: 'copy',
    )]
    #[ORM\JoinColumn(
        name: 'scan_id', 
        referencedColumnName: 'id', 
        nullable: true,
    )]
    public ?Scan $scan;

    #[ORM\ManyToOne(targetEntity: Album::class)]
    #[ORM\JoinColumn(
        name: 'album_slug',
        referencedColumnName: 'slug',
        nullable: true
    )]
    public ?Album $album = null;

    #[ORM\Column(
        name: 'created_at',
        type: 'datetime_immutable'
    )]
    public DateTimeImmutable $createdAt;
}
