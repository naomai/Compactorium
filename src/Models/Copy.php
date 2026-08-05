<?php
namespace Naomai\Compactorium\Models;

use DateTimeImmutable;
use Naomai\Compactorium\Database;

class Copy {
    public ?int $id=null;
    public int $libraryId;
    public int $ownerId;

    public int $scanId;
    public ?Scan $scan=null;

    public string $releaseMbid;
    public ?Release $release=null;

    public ?DateTimeImmutable $createdAt=null;

    public function __construct(?array $dbRow=null) {
        if($dbRow!==null) {
            $this->unserializeSql($dbRow);
        }
    }

    public function sync() {
        Database::upsert(
            'copies',
            $this->serializeSql()
        );

    }

    public function getScan() : Scan {
        return $this->scan ??= Scan::getById($this->scanId);
    }

    public function getRelease() : Release {
        return $this->scan ??= Release::findByMbid($this->releaseMbid);
    }

    public static function getById(int $id) : ?Copy {
        $db = Database::connection();
        $stm = $db->prepare("SELECT * FROM `copies` WHERE `id`=:id");
        $stm->execute(['id' => $id]);

        $row = $stm->fetch(\PDO::FETCH_ASSOC);
        if($row===false) {
            return null;
        }

        return new Copy($row);
    }

    private function unserializeSql(array $dbRow) : void {
        $this->id = $dbRow['id'];
        $this->libraryId = $dbRow['library_id'];
        $this->ownerId = $dbRow['owner_id'];
        $this->scanId = $dbRow['scan_id'];
        $this->releaseMbid = $dbRow['release_mbid'];
        $this->createdAt = Database::createDateTimeFromDbTime($dbRow['created_at']);       
    }

    private function serializeSql() : array {
        if($this->createdAt===null) {
            $this->createdAt = new DateTimeImmutable();
        }
        return [
            'id' => $this->id,
            'library_id' => $this->libraryId,
            'owner_id' => $this->ownerId,
            'scan_id' => $this->scanId,
            'release_mbid' => $this->releaseMbid,
            'created_at' => Database::createDbTimeFromDateTime($this->createdAt),
        ];
    }



}
