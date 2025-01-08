<?php


namespace sigawa\mvccore\db;


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
        } else {
            // Insert a new record
            $params = array_map(fn($attr) => ":$attr", $attributes);
            $sql = "INSERT INTO $tableName (" . implode(", ", $attributes) . ") 
                VALUES (" . implode(", ", $params) . ")";
            $statement = self::prepare($sql);

            foreach ($attributes as $attribute) {
                $statement->bindValue(":$attribute", $this->{$attribute});
            }
        }

        // Execute the query
        $statement->execute();

        // If it's an insert, fetch and assign the last inserted ID
        if (empty($this->{$primaryKey})) {
            $this->{$primaryKey} = Application::$app->db->lastInsertId();
        }

        return true;
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
