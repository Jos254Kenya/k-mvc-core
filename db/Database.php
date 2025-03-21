<?php


namespace sigawa\mvccore\db;


use sigawa\mvccore\Application;

class Database
{

    public \PDO $pdo;

    public function __construct($dbConfig = [])
    {
        $dbDsn = $dbConfig['dsn'] ?? '';
        $username = $dbConfig['user'] ?? '';
        $password = $dbConfig['password'] ?? '';
        $this->pdo = new \PDO($dbDsn, $username, $password);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function applyMigrations()
    {
        $this->createMigrationsTable();
        $appliedMigrations = $this->getAppliedMigrations();
        $migrationFiles = scandir(Application::$ROOT_DIR . '/migrations');

        $newMigrations = array_diff($migrationFiles, $appliedMigrations);

        if (empty($newMigrations)) {
            $this->log("No new migrations to apply.");
            return;
        }

        try {
            $this->beginTransaction();
            foreach ($newMigrations as $migration) {
                if ($migration === '.' || $migration === '..') continue;

                require_once Application::$ROOT_DIR . '/migrations/' . $migration;
                $className = pathinfo($migration, PATHINFO_FILENAME);
                $instance = new $className();

                $this->log("Applying migration $migration...");
                $instance->up();
                $this->log("Successfully applied $migration.");

                $this->saveMigrations($migration);
            }
            $this->commit();
        } catch (\Exception $e) {
            $this->rollBack();
            $this->log("Migration failed: " . $e->getMessage());
        }
    }

    public function rollbackLastMigration()
    {
        $lastMigration = $this->getLastMigration();
        if (!$lastMigration) {
            $this->log("No migrations to rollback.");
            return;
        }

        require_once Application::$ROOT_DIR . '/migrations/' . $lastMigration;
        $className = pathinfo($lastMigration, PATHINFO_FILENAME);
        $instance = new $className();

        $this->log("Rolling back $lastMigration...");
        $instance->down();
        $this->removeMigration($lastMigration);
        $this->log("$lastMigration rollback successful.");
    }
    protected function removeMigration(string $migration)
{
    $statement = $this->pdo->prepare("DELETE FROM migrations WHERE migration = :migration");
    $statement->execute(['migration' => $migration]);
}

    public function refreshMigrations()
    {
        $this->log("Refreshing all migrations...");
        $this->rollbackAllMigrations();
        $this->applyMigrations();
    }


    protected function createMigrationsTable()
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )  ENGINE=INNODB;");
    }

    protected function getAppliedMigrations()
    {
        $statement = $this->pdo->prepare("SELECT migration FROM migrations");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    protected function saveMigrations(array $newMigrations)
    {
        $str = implode(',', array_map(fn($m) => "('$m')", $newMigrations));
        $statement = $this->pdo->prepare("INSERT INTO migrations (migration) VALUES  $str ");
        $statement->execute();
    }
    protected function getLastMigration(): ?string
    {
        $statement = $this->pdo->prepare("SELECT migration FROM migrations ORDER BY id DESC LIMIT 1");
        $statement->execute();
        return $statement->fetchColumn() ?: null;
    }
 
    public function rollbackAllMigrations()
    {
        $appliedMigrations = $this->getAppliedMigrations();
    
        if (empty($appliedMigrations)) {
            $this->log("No migrations to rollback.");
            return;
        }
    
        $this->beginTransaction();
    
        try {
            foreach (array_reverse($appliedMigrations) as $migration) {
                require_once Application::$ROOT_DIR . '/migrations/' . $migration;
                $className = pathinfo($migration, PATHINFO_FILENAME);
                $instance = new $className();
    
                $this->log("Rolling back $migration...");
                $instance->down();
                $this->removeMigration($migration);
                $this->log("$migration rollback successful.");
            }
    
            $this->commit();
        } catch (\Exception $e) {
            $this->rollBack();
            $this->log("Rollback failed: " . $e->getMessage());
        }
    }
            
    public function prepare($sql): \PDOStatement
    {
        return $this->pdo->prepare($sql);
    }

    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }
    private function log($message)
    {
        echo "[" . date("Y-m-d H:i:s") . "] - " . $message . PHP_EOL;
    }

    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    public function commit()
    {
        return $this->pdo->commit();
    }

    public function rollBack()
    {
        return $this->pdo->rollBack();
    }
}
