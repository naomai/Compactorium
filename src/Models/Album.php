<?php
namespace Naomai\Compactorium\Models;

use DateTimeImmutable;
use Naomai\Compactorium\Database;

class Album {
    public string $mbid;
    public string $title;
    public string $artist;
    public int $year;
    public object $musicbrainzData;
    public ?DateTimeImmutable $createdAt=null;

    public function __construct(?array $dbRow=null) {
        if($dbRow!==null) {
            $this->unserializeSql($dbRow);
        }
    }

    public function sync() {
        Database::upsert(
            'albums',
            $this->serializeSql()
        );

    }

    public static function findByMbid(string $mbid) : ?Album {
        $db = Database::connection();
        $stm = $db->prepare("SELECT * FROM `albums` WHERE `release_group_mbid`=:mbid");
        $stm->execute(['mbid' => $mbid]);

        $row = $stm->fetch(\PDO::FETCH_ASSOC);
        if($row===false) {
            return null;
        }

        return new Album($row);
    }

    private function unserializeSql(array $dbRow) : void {
        $this->mbid = $dbRow['release_group_mbid'];
        $this->title = $dbRow['title'];
        $this->artist = $dbRow['artist'];
        $this->year = $dbRow['year'];
        $this->musicbrainzData = json_decode($dbRow['musicbrainz_json']);
        $this->createdAt = Database::createDateTimeFromDbTime($dbRow['created_at']);       
    }

    private function serializeSql() : array {
        if($this->createdAt===null) {
            $this->createdAt = new DateTimeImmutable();
        }
        return [
            'release_group_mbid' => $this->mbid,
            'title' => $this->title,
            'artist' => $this->artist,
            'year' => $this->year,
            'musicbrainz_json' => json_encode($this->musicbrainzData),
            'created_at' => Database::createDbTimeFromDateTime($this->createdAt),
        ];
    }

    public static function getYearFromMbDate(string $mbDate) : ?int {
        preg_match('/^(\d{4})/', $mbDate, $m);
        return (int)$m[1] ?? null;
    }

}
