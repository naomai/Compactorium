<?php
namespace Naomai\Compactorium\Models;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'albums')]
class Album {
    #[ORM\Id]
    #[ORM\Column(
        name: 'slug',
        type: 'string'
    )]
    public string $releaseGroupMbid;

    #[ORM\Column(type: 'string')]
    public string $title;

    #[ORM\Column(type: 'string')]
    public string $artist;

    #[ORM\Column(type: 'string')]
    public string $year;

    #[ORM\Column(
        name: 'raw_json',
        type: 'json',
        nullable: true
    )]
    public ?array $musicbrainzJson = null;

    #[ORM\Column(
        name: 'created_at',
        type: 'datetime_immutable'
    )]
    public DateTimeImmutable $createdAt;

    public static function getYearFromMbDate(string $mbDate) : ?int {
        preg_match('/^(\d{4})/', $mbDate, $m);
        return (int)$m[1] ?? null;
    }

}
