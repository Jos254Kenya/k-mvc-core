<?php


namespace sigawa\mvccore\db;

use PDO;
use sigawa\mvccore\Application;

use sigawa\mvccore\Model;

abstract class DbModel extends Model
{
    abstract public static function tableName(): string;

    public static function primaryKey(): string
    {
        return 'id';
    }


    public function save()
    {
        $tableName = static::tableName();
        $attributes = $this->attributes();
        $primaryKey = static::primaryKey();
        // Check if the record exists (for update or insert decision)
        if (!empty($this->{$primaryKey})) {
            // Update the existing record
            $params = array_map(fn($attr) => "$attr = :$attr", $attributes);
            $sql = "UPDATE $tableName SET " . implode(", ", $params) . " WHERE $primaryKey = :$primaryKey";
            $statement = self::prepare($sql);

            foreach ($attributes as $attribute) {
                $statement->bindValue(":$attribute", $this->{$attribute});
            }
            $statement->bindValue(":$primaryKey", $this->{$primaryKey});

            // Execute the update query
            $statement->execute();
            // Check if any rows were affected by the update
            if ($statement->rowCount() > 0) {
                return true;  // Update was successful and affected rows
            } else {
                return false;  // No rows were updated
            }
        } else {
            // Insert a new record
            $params = array_map(fn($attr) => ":$attr", $attributes);
            $sql = "INSERT INTO $tableName (" . implode(", ", $attributes) . ") 
                    VALUES (" . implode(", ", $params) . ")";
            $statement = self::prepare($sql);

            foreach ($attributes as $attribute) {
                $statement->bindValue(":$attribute", $this->{$attribute});
            }

            // Execute the insert query
            $statement->execute();

            // If it's an insert, fetch and assign the last inserted ID
            if (empty($this->{$primaryKey})) {
                $this->{$primaryKey} = Application::$app->db->lastInsertId();
            }

            return true;  // Insert was successful
        }
    }
    private static function getTableColumns(string $tableName): array
    {
        $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = :tableName AND TABLE_SCHEMA = DATABASE()";
        $statement = self::prepare($sql);
        $statement->bindValue(":tableName", $tableName);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function joinWith(string $joinTable, string $foreignKey, string $localKey, array $conditions = [], array $columns = ['*']): array
    {
        if (!method_exists(static::class, 'tableName')) {
            throw new \Exception("tableName() method is missing in " . static::class);
        }

        $tableName = static::tableName();
        $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);
        $safeJoinTable = preg_replace('/[^a-zA-Z0-9_]/', '', $joinTable);

        // Fetch actual columns dynamically
        $mainTableColumns = self::getTableColumns($safeTable);
        $joinTableColumns = self::getTableColumns($safeJoinTable);

        if (empty($mainTableColumns) || empty($joinTableColumns)) {
            throw new \Exception("Failed to retrieve columns for '$safeTable' or '$safeJoinTable'.");
        }

        $columnSelections = [];

        // Handle `*` wildcard selection
        if (in_array('*', $columns) || in_array("$safeTable.*", $columns) || in_array("$safeJoinTable.*", $columns)) {
            if (in_array('*', $columns) || in_array("$safeTable.*", $columns)) {
                foreach ($mainTableColumns as $col) {
                    $columnSelections[] = "`$safeTable`.`$col` AS `{$safeTable}_$col`";
                }
            }
            if (in_array('*', $columns) || in_array("$safeJoinTable.*", $columns)) {
                foreach ($joinTableColumns as $col) {
                    $columnSelections[] = "`$safeJoinTable`.`$col` AS `{$safeJoinTable}_$col`";
                }
            }
        } else {
            foreach ($columns as $column) {
                if (strpos($column, '.') !== false) {
                    [$alias, $colName] = explode('.', $column, 2);
                    $safeCol = preg_replace('/[^a-zA-Z0-9_]/', '', $colName);
                    $columnSelections[] = "`$alias`.`$safeCol` AS `{$alias}_$safeCol`";
                } else {
                    $safeColumn = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
                    if (in_array($safeColumn, $mainTableColumns)) {
                        $columnSelections[] = "`$safeTable`.`$safeColumn` AS `{$safeTable}_$safeColumn`";
                    } elseif (in_array($safeColumn, $joinTableColumns)) {
                        $columnSelections[] = "`$safeJoinTable`.`$safeColumn` AS `{$safeJoinTable}_$safeColumn`";
                    }
                }
            }
        }

        if (empty($columnSelections)) {
            throw new \Exception("No valid columns selected for query.");
        }

        $columnsString = implode(', ', array_unique($columnSelections));
        $sql = "SELECT $columnsString FROM `$safeTable` JOIN `$safeJoinTable` ON `$safeTable`.`$localKey` = `$safeJoinTable`.`$foreignKey`";

        if (!empty($conditions)) {
            $clauses = [];
            foreach ($conditions as $column => $value) {
                if (strpos($column, '.') !== false) {
                    [$tableAlias, $colName] = explode('.', $column, 2);
                    $safeCol = preg_replace('/[^a-zA-Z0-9_]/', '', $colName);
                    $clauses[] = "`$tableAlias`.`$safeCol` = :$safeCol";
                } else {
                    $safeCol = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
                    $clauses[] = "`$safeTable`.`$safeCol` = :$safeCol";
                }
            }
            $sql .= " WHERE " . implode(" AND ", $clauses);
        }

        $statement = self::prepare($sql);
        foreach ($conditions as $key => $value) {
            if (strpos($key, '.') !== false) {
                [, $colName] = explode('.', $key, 2);
                $safeKey = preg_replace('/[^a-zA-Z0-9_]/', '', $colName);
            } else {
                $safeKey = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
            }
            $statement->bindValue(":$safeKey", $value);
        }

        $statement->execute();
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }


    public static function findAllByQuery(string $query, array $params = [])
    {
        $statement = self::prepare($query);

        foreach ($params as $key => $value) {
            $statement->bindValue(":$key", $value);
        }

        $statement->execute();
        return $statement->fetchAll(\PDO::FETCH_ASSOC); // Fetch all as associative array
    }

    public static function query(string $query, array $params = [])
    {
        $statement = self::prepare($query);

        foreach ($params as $key => $value) {
            $statement->bindValue(":$key", $value);
        }

        return $statement->execute(); // Execute and return success/failure
    }

    public static function updateOrCreate(array $conditions, array $data)
    {
        // Check if a record exists
        $existingRecord = static::findOne($conditions);
        if ($existingRecord) {
            // Update existing record
            foreach ($data as $key => $value) {
                $existingRecord->$key = $value;
            }
            return $existingRecord->save() ? $existingRecord : false;
        }
        // Create new record
        $newRecord = new static();
        foreach (array_merge($conditions, $data) as $key => $value) {
            $newRecord->$key = $value;
        }

        return $newRecord->save() ? $newRecord : false;
    }


    public static function prepare($sql): \PDOStatement
    {
        return Application::$app->db->prepare($sql);
    }
    public function delete(): bool
    {
        $tableName = static::tableName();
        $primaryKey = static::primaryKey();

        if (!empty($this->{$primaryKey})) {
            $sql = "DELETE FROM $tableName WHERE $primaryKey = :$primaryKey";
            $statement = self::prepare($sql);
            $statement->bindValue(":$primaryKey", $this->{$primaryKey});

            return $statement->execute();
        }

        return false;
    }
    public function softDelete($status = 0): bool
    {
        $tableName = static::tableName();
        $primaryKey = static::primaryKey();

        // Fetch table columns
        $statement = Application::$app->db->prepare("SHOW COLUMNS FROM `$tableName`");
        $statement->execute();
        $columns = array_column($statement->fetchAll(\PDO::FETCH_ASSOC), 'Field'); // Extract column names

        // Determine the soft delete column
        $softDeleteColumn = null;
        $deleteValue = null;

        if (in_array('deleted_at', $columns)) {
            $softDeleteColumn = 'deleted_at';
            $deleteValue = date('Y-m-d H:i:s'); // Timestamp for deleted_at
        } elseif (in_array('status', $columns)) {
            $softDeleteColumn = 'status';
            $deleteValue = $status; // Default to 0 for inactive
        }

        if ($softDeleteColumn && !empty($this->{$primaryKey})) {
            $sql = "UPDATE `$tableName` SET `$softDeleteColumn` = :deleteValue WHERE `$primaryKey` = :primaryKey";
            $statement = self::prepare($sql);
            $statement->bindValue(":deleteValue", $deleteValue);
            $statement->bindValue(":primaryKey", $this->{$primaryKey});

            return $statement->execute();
        }

        return false;
    }


    public static function deleteAll(array $conditions): bool
    {
        $tableName = static::tableName();

        if (empty($conditions)) {
            throw new \Exception('Delete conditions cannot be empty.');
        }

        $whereClause = [];
        $parameters = [];

        foreach ($conditions as $column => $value) {
            $whereClause[] = "`$column` = :$column";
            $parameters[":$column"] = $value;
        }

        $sql = "DELETE FROM `$tableName` WHERE " . implode(' AND ', $whereClause);

        $statement = self::prepare($sql);

        foreach ($parameters as $key => $value) {
            $statement->bindValue($key, $value);
        }

        return $statement->execute();
    }

    public function createId()
    {
        return Application::$app->db->lastInsertId();
    }
    public static function findOne($where)
    {
        $tableName = static::tableName();
        $attributes = array_keys($where);
        $conditions = implode(" AND ", array_map(fn($attr) => "$attr = :$attr", $attributes));
        $sql = "SELECT * FROM $tableName WHERE $conditions LIMIT 1";
        $statement = self::prepare($sql);

        foreach ($where as $key => $value) {
            $statement->bindValue(":$key", $value);
        }

        $statement->execute();
        $result = $statement->fetchObject(static::class);

        return $result !== false ? $result : null;
    }

    public static function findAll(array $where = [])
    {
        $tableName = static::tableName();
        $sql = "SELECT * FROM $tableName";

        if (!empty($where)) {
            $attributes = array_keys($where);
            $conditions = implode(" AND ", array_map(fn($attr) => "$attr = :$attr", $attributes));
            $sql .= " WHERE $conditions";
        }

        $statement = self::prepare($sql);

        foreach ($where as $key => $value) {
            $statement->bindValue(":$key", $value);
        }

        $statement->execute();
        return $statement->fetchAll(\PDO::FETCH_CLASS, static::class);
    }
}
