<?php
namespace Naomai\Compactorium\Models;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'releases')]
class Release {
    #[ORM\Id]
    #[ORM\Column(
        name: 'release_mbid',
        type: 'string',
        length: 36
    )]
    public string $releaseMbid;

    #[ORM\ManyToOne(targetEntity: Album::class)]
    #[ORM\JoinColumn(
        name: 'release_group_mbid',
        referencedColumnName: 'release_group_mbid',
        nullable: false
    )]
    public Album $album;

    #[ORM\Column(type: 'string')]
    public string $barcode;

    #[ORM\Column(
        name: 'musicbrainz_json',
        type: 'json',
        nullable: true
    )]
    public ?array $musicbrainzJson = null;

    #[ORM\Column(
        name: 'created_at',
        type: 'datetime_immutable'
    )]
    public DateTimeImmutable $createdAt;
}