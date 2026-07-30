<?php
    namespace Naomai\Compactorium;

    class Migration {
        private static string $migrationsPath;
        private static \PDO $db;

        public static function init() : void {
            self::$migrationsPath = $_ENV['BASE_DIR'] . "/database/migrations";
            self::$db = Database::connection();
            self::bootstrap(self::$db);
        }

        private static function bootstrap(\PDO $db) : void {
            $db->query("
                CREATE TABLE IF NOT EXISTS `schema_migrations` (
                    version_id INTEGER PRIMARY KEY,
                    name TEXT NOT NULL,
                    applied_at TEXT NOT NULL
                )
                ", \PDO::FETCH_ASSOC);
        }

        public static function run() : void {
            $db = self::$db;
            $migrations = self::getPendingMigrations($db);

            sort($migrations);

            foreach($migrations as $migration) {
                $fullPath = realpath(self::$migrationsPath . "/" . $migration . ".sql");
                self::applyMigrationFile($db, $fullPath);
                self::markMigrationApplied($db, $migration);
            }

        }

        private static function getPendingMigrations(\PDO $db) : array {
            $applied = self::getAppliedMigrations($db);
            return array_filter(
                self::getMigrationFiles(),
                fn($migrationName) => !in_array($migrationName, $applied)
            );
        }
        
        private static function getAppliedMigrations(\PDO $db) : array {
            $stm = $db->query("SELECT * FROM `schema_migrations`", \PDO::FETCH_ASSOC);
            
            return array_column($stm->fetchAll(), "name");
        }

        private static function getMigrationFiles() : array {
            $migrationFiles = array_map(fn($path)=>
                pathinfo($path, PATHINFO_FILENAME),
                glob(self::$migrationsPath . "/*_*.sql")
            );
            return $migrationFiles;
        }


        private static function applyMigrationFile(\PDO $db, string $filePath) : void {
            $db->beginTransaction();
            try {
                $sql = file_get_contents($filePath);
                $db->exec($sql);
                $db->commit();
            }
            catch (\Exception $e) {
                $db->rollBack();
                throw $e;
            }
        }

        private static function markMigrationApplied(\PDO $db, string $migrationName) : void {
            $versionId = (int)substr($migrationName, 0, 3);

            $db->prepare("INSERT INTO `schema_migrations` 
                (version_id, name, applied_at) VALUES
                (:version_id, :name, :applied_at)
            ")->execute([
                'version_id'=> $versionId,
                'name' => $migrationName,
                'applied_at' => Database::timeNow(),
            ]);
        }


    }
    