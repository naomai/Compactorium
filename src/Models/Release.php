<?php
namespace Naomai\Compactorium\Models;

use DateTimeImmutable;
use Naomai\Compactorium\Database;

class Release {
    public string $mbid;
    public string $groupMbid;
    public string $barcode;
    public object $musicbrainzData;
    public ?DateTimeImmutable $createdAt=null;

    public function __construct(?array $dbRow=null) {
        if($dbRow!==null) {
            $this->unserializeSql($dbRow);
        }
    }

    public function sync() {
        Database::upsert(
            'releases',
            $this->serializeSql()
        );

    }

    public static function findByMbid(string $mbid) : ?Release {
        $db = Database::connection();
        $stm = $db->prepare("SELECT * FROM `releases` WHERE `release_mbid`=:mbid");
        $stm->execute(['mbid' => $mbid]);

        $row = $stm->fetch(\PDO::FETCH_ASSOC);
        if($row===false) {
            return null;
        }

        return new Release($row);
    }

    private function unserializeSql(array $dbRow) : void {
        $this->mbid = $dbRow['release_mbid'];
        $this->groupMbid = $dbRow['release_group_mbid'];
        $this->barcode = $dbRow['barcode'];
        $this->musicbrainzData = json_decode($dbRow['musicbrainz_json']);
        $this->createdAt = Database::createDateTimeFromDbTime($dbRow['created_at']);       
    }

    private function serializeSql() : array {
        if($this->createdAt===null) {
            $this->createdAt = new DateTimeImmutable();
        }
        return [
            'release_mbid' => $this->mbid,
            'release_group_mbid' => $this->groupMbid,
            'barcode' => $this->barcode,
            'musicbrainz_json' => json_encode($this->musicbrainzData),
            'created_at' => Database::createDbTimeFromDateTime($this->createdAt),
        ];
    }



}
