<?php
namespace Naomai\Compactorium\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Naomai\Compactorium\Database;
use Naomai\Compactorium\Slugger;

#[ORM\Entity]
#[ORM\Table(name: 'albums')]
class Album {
    #[ORM\Id]
    #[ORM\Column(
        name: 'slug',
        type: 'string'
    )]
    public string $slug;

    #[ORM\Column(type: 'string')]
    public string $title;

    #[ORM\Column(type: 'string')]
    public string $artist;

    #[ORM\Column(type: 'string')]
    public string $year;

    #[ORM\Column(
        name: 'raw_json',
        type: 'json_object',
        nullable: true
    )]
    public ?object $rawJson = null;

    #[ORM\Column(
        name: 'created_at',
        type: 'datetime_immutable'
    )]
    public DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'string')]
    public ?string $image;

    #[ORM\OneToMany(
        mappedBy: 'album',
        targetEntity: Barcode::class
    )]
    public Collection $barcodes;

    public function __construct() {
        $this->barcodes = new ArrayCollection();
    }

    public static function getYearFromMbDate(string $mbDate) : ?int {
        preg_match('/^(\d{4})/', $mbDate, $m);
        return (int)$m[1] ?? null;
    }

    public static function fromArtistAndTitle(string $artist, string $title): ?self {
        $slug = Slugger::slugFromArtistAndAlbum($artist, $title);

        $album = Database::entityManager()
            ->getRepository(self::class)
            ->find($slug);

        return $album;
    }



}
