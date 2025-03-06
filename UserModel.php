<?php

namespace sigawa\mvccore;

use sigawa\mvccore\db\DbModel;

abstract class UserModel extends DbModel
{
    public int $id;
    public ?string $session_token = null; // Allow null for unauthenticated users
    public ?string $role = 'user'; // Default role if not assigned
    public ?string $email = 'email@email.com'; // Default role if not assigned

    abstract public function getDisplayName(): string;
    abstract public function getPermissions(): array; // Each user can define their permissions

    public function update(array $data): bool
    {
        $table = static::tableName();
        $primaryKey = static::primaryKey();
        $id = $this->{$primaryKey};
        
        $columns = array_keys($data);
        $setClause = implode(', ', array_map(fn($col) => "$col = :$col", $columns));

        $sql = "UPDATE $table SET $setClause WHERE $primaryKey = :id";

        $stmt = Application::$app->db->prepare($sql);
        if (!$stmt) {
            return false; // Return false if prepare() fails
        }

        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->bindValue(':id', $id);

        return $stmt->execute();
    }
}
