<?php

namespace Mountkenymilk\Fems\helper;

use sigawa\mvccore\Application;
use sigawa\mvccore\db\DbModel;

class PermissionHelper extends DbModel
{
    public static function tableName(): string
    {
        return 'permissions';
    }


    /**
     * Fetches permission columns dynamically from the database.
     */
    private static function getPermissionColumns(): array
    {
        $stmt = self::prepare("
            SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_NAME = ? 
            AND TABLE_SCHEMA = DATABASE()
            AND COLUMN_NAME NOT IN ('role_id', 'id')
        ");
        $stmt->execute([self::tableName()]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
    public static function saveOrUpdateRolePermissions(int $roleId, array $permissions)
    {
        $db = Application::$app->db;
        $db->beginTransaction();

        try {
            // Fetch all dynamic permission columns
            $permissionColumns = self::getPermissionColumns();

            // Prepare column names dynamically
            $columns = array_merge(['role_id'], $permissionColumns);
            $placeholders = array_map(fn($col) => ":$col", $columns);

            // Construct ON DUPLICATE KEY UPDATE dynamically
            $updateStatements = array_map(fn($col) => "$col = VALUES($col)", $permissionColumns);

            // Construct query dynamically
            $sql = sprintf(
                "INSERT INTO permissions (%s) VALUES (%s) 
                 ON DUPLICATE KEY UPDATE %s",
                implode(", ", $columns),
                implode(", ", $placeholders),
                implode(", ", $updateStatements)
            );

            // Prepare query
            $stmt = $db->prepare($sql);

            // Bind values
            $stmt->bindValue(':role_id', $roleId, \PDO::PARAM_INT);
            foreach ($permissionColumns as $col) {
                $stmt->bindValue(":$col", $permissions[$col] ?? 'No', \PDO::PARAM_STR);
            }

            // Execute query
            $stmt->execute();

            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            throw new \Exception("Error updating role permissions: " . $e->getMessage());
        }
    }
    public static function getBlankPermissions(): array
    {
        $columns = self::getPermissionColumns();
        if (empty($columns)) return [];

        // Initialize all permissions as "No"
        $permissions = array_fill_keys($columns, "No");

        return $permissions;
    }
    /**
     * Fetch all permissions for a role with CASE WHEN logic.
     */
    public  function fetchPermissions($roleId): array
    {
        $columns = $this->getPermissionColumns();
        if (empty($columns)) return [];

        // Construct CASE WHEN SQL dynamically
        $columnCases = array_map(fn($col) => "CASE WHEN p.$col = 'Yes' THEN p.$col ELSE NULL END AS $col", $columns);
        $columnCasesString = implode(', ', $columnCases);

        $stmt = self::prepare("
            SELECT r.id AS roleid, $columnCasesString
            FROM roles r
            INNER JOIN permissions p ON p.role_id = r.id
            WHERE r.id = ?
        ");
        $stmt->execute([$roleId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Fetch only the 'Yes' permissions for a role.
     */
    public function fetchYesPermissions($roleId): array
    {
        $columns = $this->getPermissionColumns();
        if (empty($columns)) return [];

        // Construct CASE WHEN SQL dynamically
        $columnCases = array_map(fn($col) => "CASE WHEN p.$col = 'Yes' THEN p.$col ELSE NULL END AS $col", $columns);
        $columnCasesString = implode(', ', $columnCases);

        $stmt = self::prepare("
            SELECT r.id AS roleid, $columnCasesString
            FROM roles r
            INNER JOIN permissions p ON p.role_id = r.id
            WHERE r.id = ?
        ");
        $stmt->execute([$roleId]);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$result) return [];

        // Filter to keep only columns with 'Yes' values
        return array_filter($result, fn($value) => $value === 'Yes');
    }
}
