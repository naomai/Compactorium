<?php
namespace Naomai\Compactorium\Models;

use DateTimeImmutable;
use Naomai\Compactorium\Database;

class Scan {
    public ?int $id = null;
    public int $ownerId;
    public int $libraryId;
    public string $barcode;
    public bool $processed = false;
    public ?DateTimeImmutable $scannedAt=null;

    public function __construct(?array $dbRow=null) {
        if($dbRow !== null) {
            $this->unserializeSql($dbRow);
        }
    }

    public function sync() {
        Database::upsert(
            'scans',
            $this->serializeSql()
        );

    }

    public static function getById(int $id) : ?Scan {
        $db = Database::connection();
        $stm = $db->prepare("SELECT * FROM `scans` WHERE `id`=:id");
        $stm->execute(['id' => $id]);

        $row = $stm->fetch(\PDO::FETCH_ASSOC);
        if($row===false) {
            return null;
        }

        return new Scan($row);
    }

    private function unserializeSql(array $dbRow) : void {
        $this->id = $dbRow['id'];
        $this->ownerId = $dbRow['owner_id'];
        $this->libraryId = $dbRow['library_id'];
        $this->barcode = $dbRow['barcode'];
        $this->processed = $dbRow['processed'];
        $this->scannedAt = Database::createDateTimeFromDbTime($dbRow['scanned_at']);       
    }

    private function serializeSql() : array {
        if($this->scannedAt===null) {
            $this->scannedAt = new DateTimeImmutable();
        }
        return [
            'id' => $this->id,
            'owner_id' => $this->ownerId,
            'library_id' => $this->libraryId,
            'barcode' => $this->barcode,
            'processed' => $this->processed,
            'scanned_at' => Database::createDbTimeFromDateTime($this->scannedAt),
        ];
    }



}
