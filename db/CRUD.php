<?php


namespace sigawa\mvccore\db;

use PDOException;

class CRUD
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }
    /**
     * Saves data to the specified table using provided attributes and data.
     *
     * @param string $tableName The name of the table to save data to.
     * @param array $attributes An array of column names in the table.
     * @param array $data An associative array where keys are column names and values are data to be saved.
     * @return bool True if the data is successfully saved, false otherwise.
     */

    public function saveGeneric(string $tableName, array $attributes, array $data): bool
    {
        // Prepare the SQL statement`
        $params = array_map(fn($attr) => ":$attr", $attributes);
        $sql = "INSERT INTO $tableName (" . implode(",", $attributes) . ") 
            VALUES (" . implode(",", $params) . ")";
        $statement = self::prepare($sql);
        // Bind values to parameters
        foreach ($attributes as $attribute) {
            if (!array_key_exists($attribute, $data)) {
                throw new InvalidArgumentException("Data for attribute '$attribute' is missing.");
            }
            $statement->bindValue(":$attribute", $data[$attribute]);
        }
        // Execute the SQL statement
        $success = $statement->execute();

        return $success;
    }
    public function prepare($sql): \PDOStatement
    {
        return $this->db->prepare($sql);
    }
    /**
     * @param $table
     * @param $data
     * @param $conditions
     * @return bool
     */

    /**
     * Get column names for a given table.
     *
     * @param string $tableName The name of the table.
     * @return array|false An array of column names, or false on failure.
     */
    public function getColumnNames(string $tableName, array $excludeColumns = []): array|false
    {
        // Prepare the SQL statement to describe the table
        $sql = "DESCRIBE $tableName";

        try {
            // Prepare the statement
            $statement = self::prepare($sql);

            // Execute the query
            $statement->execute();

            // Fetch all rows as associative array
            $columns = $statement->fetchAll(\PDO::FETCH_ASSOC);

            // Extract column names from the result, excluding the specified columns
            $columnNames = [];
            foreach ($columns as $column) {
                // Check if the column should be excluded
                if (!in_array($column['Field'], $excludeColumns)) {
                    $columnNames[] = $column['Field'];
                }
            }

            return $columnNames;
        } catch (PDOException $e) {
            // Log or handle the exception appropriately
            error_log("Error fetching column names: " . $e->getMessage());
            return false;
        }
    }

    public function readAll($tables, $columns = '*', $conditions = [])
    {
        // Ensure $tables is an array
        $tables = (array) $tables;

        // Build the SQL query
        $sql = 'SELECT ' . $columns . ' FROM ' . implode(', ', $tables);

        // Add conditions if provided
        if (!empty($conditions)) {
            $whereClause = implode(' AND ', array_map(function ($key) {
                return "$key = :$key";
            }, array_keys($conditions)));
            $sql .= ' WHERE ' . $whereClause;
        }
        // Prepare the statement
        $stmt = self::prepare($sql);
        // Bind parameters
        foreach ($conditions as $key => &$value) {
            $stmt->bindParam(":$key", $value); // Use named placeholders
        }
        // Execute the query
        $stmt->execute();

        // Fetch all rows as an associative array
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Count the number of results
        $count = count($results);

        return ['data' => $results, 'count' => $count];
    }

    /**
     *  Performs a flexible SELECT operation on a database table.
     *
     * @param string $table The name of the table to select from.
     * @param array $fields An array of field names to select.
     * @param array $conditions An associative array of conditions in the form ['column' => 'value'].
     * @param string $joins Optional JOIN clauses for the query.
     * @param int|null $limit Optional limit for the number of rows to retrieve.
     * @return array The result set as an associative array.
     * @throws PDOException If an error occurs during database query execution.
     * The readTableAdvanced function is designed to perform a flexible SELECT operation on a database table.
     * It constructs and executes a SQL query based on the provided parameters, including fields to select, table name,
     * conditions, joins, and an optional limit.
     */
    public function readTableAdvanced(string $table, string $tableAlias, array $fields, array $conditions = [], string $joins = '', int $limit = null): array
    {
        // Construct the SELECT query dynamically
        $query = 'SELECT ' . implode(', ', $fields) . ' FROM ' . $table . ' ' . $tableAlias;
        // Handle JOIN clauses
        if (!empty($joins)) {
            $query .= ' ' . $joins;
        }
        // Construct the WHERE clause if conditions are provided
        $whereClause = '';
        if (!empty($conditions)) {
            $whereClause = ' WHERE ' . implode(' AND ', array_map(function ($key) use ($tableAlias) {
                    return "$tableAlias.$key = :$key";
                }, array_keys($conditions)));
        }
        $query .= $whereClause;
        // Add the LIMIT clause if provided
        if ($limit !== null) {
            $query .= ' LIMIT ' . (int)$limit;
        }

        // Debugging: echo out the SQL query
              // Prepare the query
        $statement = self::prepare($query);
        // Bind parameters if conditions are provided
        if (!empty($conditions)) {
            foreach ($conditions as $key => $value) {
                $statement->bindValue(":$key", $value);
            }
        }
        // Execute the query
        $statement->execute();
        // Fetch and return the result in associative array
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function readAdvancedJoins(string $table, string $tableAlias, array $fields, array $conditions = [], array $joins = [], ?string $orderBy = null, int $limit = null): array
{
    // Construct the SELECT query dynamically
    $query = 'SELECT ' . implode(', ', $fields) . ' FROM ' . $table . ' ' . $tableAlias;

    // Handle JOIN clauses
    foreach ($joins as $join) {
        $query .= ' ' . $join;
    }

    // Construct the WHERE clause if conditions are provided
    $whereClause = '';
    if (!empty($conditions)) {
        $whereClause = ' WHERE ' . implode(' AND ', array_map(function ($key) use ($tableAlias) {
                return "$tableAlias.$key = :$key";
            }, array_keys($conditions)));
    }
    $query .= $whereClause;

    // Add the ORDER BY clause if provided
    if ($orderBy !== null) {
        $query .= ' ORDER BY ' . $orderBy;
    }

    // Add the LIMIT clause if provided
    if ($limit !== null) {
        $query .= ' LIMIT ' . (int)$limit;
    }

    // Prepare the query
    $statement = self::prepare($query);

    // Bind parameters if conditions are provided
    if (!empty($conditions)) {
        foreach ($conditions as $key => $value) {
            $statement->bindValue(":$key", $value);
        }
    }

    // Execute the query
    $statement->execute();

    // Fetch and return the result in associative array
    $result['data'] = $statement->fetchAll(\PDO::FETCH_ASSOC);

    // Count the number of rows returned
    $result['count'] = count($result['data']);

    return $result;
}

    /**
     * Updates data in the specified table based on the provided data and conditions.
     *
     * @param string $table The name of the table to update data in.
     * @param array $data An associative array of columns and their new values to be updated.
     * @param array $conditions An associative array of conditions for the update operation.
     * @return bool True if the update is successful, false otherwise.
     */
    public function update(string $table, array $data, array $conditions): array
    {
        // Construct the SQL statement
        $sql = "UPDATE $table SET ";
        $updates = [];
        foreach ($data as $key => $value) {
            $updates[] = "$key = :$key";
        }
        $sql .= implode(', ', $updates);
        if (!empty($conditions)) {
            $sql .= " WHERE ";
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "$key = :$key";
            }
            $sql .= implode(' AND ', $where);
        }

        try {
            // Prepare the SQL statement
            $stmt = self::prepare($sql);

            // Bind parameters for data
            foreach ($data as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }

            // Bind parameters for conditions
            foreach ($conditions as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            // Execute the prepared statement
            $stmt->execute();
            // Check if any row was affected
            $changesMade = $stmt->rowCount() > 0;
            return ['success' => true, 'changesMade' => $changesMade];
        } catch (\PDOException $e) {
            // Log or handle the exception appropriately
            error_log("Error updating data: " . $e->getMessage());
            return ['success' => false, 'changesMade' => false];
        }
    }


    /**
     * @param $tables
     * @param string $columns
     * @param array $conditions
     * @return array
     */

    /**
     * Deletes data from the specified table based on the provided conditions.
     *
     * @param string $tableName The name of the table from which data will be deleted.
     * @param array $conditions An associative array of conditions for the deletion operation.
     *                          Each condition may include comparison operators (<, >, <=, >=).
     *                          Example: ['id' => 1, 'age >=' => 30]
     * @return bool True if the deletion is successful, false otherwise.
     */
    public function delete(string $tableName, array $conditions): bool
    {
        // Construct the DELETE query
        $sql = "DELETE FROM $tableName";

        // Add conditions if provided
        if (!empty($conditions)) {
            $whereClause = ' WHERE ' . implode(' AND ', array_map(function ($key, $value) {
                    // Handle comparison operators in conditions
                    if (strpos($key, ' ') !== false) {
                        return "$key :$key";
                    } else {
                        return "$key = :$key";
                    }
                }, array_keys($conditions), $conditions));
            $sql .= $whereClause;
        }

        // Prepare the statement
        $statement = self::prepare($sql);
        // Bind parameters if conditions are provided
        foreach ($conditions as $key => &$value) {
            $statement->bindValue(":$key", $value);
        }

        // Execute the query
        $success = $statement->execute();
        return $success;
    }
    public function getAll($tables, $columns = '*', $conditions = [])
    {
        if (!is_array($tables)) {
            $tables = [$tables];
        }
        // Build the SQL query
        $sql = 'SELECT ' . $columns . ' FROM ' . implode(', ', $tables);
        // Add conditions if provided
        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', array_map(function ($key) {
                    return "$key = :$key";
                }, array_keys($conditions)));
        }

        // Prepare the statement
        $stmt = self::prepare($sql);

// Bind parameters
        foreach ($conditions as $key => &$value) {
            $stmt->bindParam($key, $value);
        }
        // Execute the query
        $stmt->execute();
        // Fetch all rows as an associative array
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }
    public function lastInsertId()
    {
        return self::lastInsertId();
    }
    function saveRoleAndPermissions(array $formData) {
        try {
            // Begin the transaction
            $this->db->beginTransaction();

            // Insert the role_name into the roles table
            $roleName = ucwords($formData['role_name']);
            $stmt = self::prepare("INSERT INTO roles (name) VALUES (:role_name)");
            $stmt->bindParam(':role_name', $roleName);

            $stmt->execute();

            // Get the last inserted role ID
            $lastRoleId = $this->db->lastInsertId();

            // Extract permissions from the form data
            $permissions = $formData['permission'];

            // Initialize the SQL query
            $sql = "INSERT INTO permissions (`role_id`";

            // Construct the column names part of the SQL query
            $columnNames = [];
            foreach ($permissions as $permission) {
                // Add the permission as a column name
                $columnNames[] = "`$permission`";
            }
            $sql .= ", " . implode(',', $columnNames) . ")";

            // Add the values part of the SQL query
            $sql .= " VALUES (:role_id";
            $values = [':role_id' => $lastRoleId];
            foreach ($permissions as $permission) {
                // Determine the value based on whether the permission exists in the form data
                $value = isset($formData['permission'][$permission]) ? 'No' : 'Yes';
                // Add the parameter and value to the list of values
                $param = ':' . $permission;
                $values[$param] = $value;
                // Add the parameter placeholder to the SQL query
                $sql .= ", $param";
            }
            $sql .= ")";


            // Prepare the SQL statement
            $stmt = self::prepare($sql);

            // Execute the SQL query with the values
            $stmt->execute($values);

            // Commit the transaction
            $this->db->commit();

            // Return true to indicate success
            return true;
        } catch (Exception $e) {
            // Rollback the transaction on error
            $this->db->rollBack();
            // Log or handle the exception appropriately
            error_log("Error saving role and permissions: " . $e->getMessage());
            // Return false to indicate failure
            return false;
        }
    }

    function updateRoleAndPermissions(array $formData) {
        try {
            // Begin the transaction
            $this->db->beginTransaction();

            // Update the role name
            $stmt = self::prepare("UPDATE roles SET name = :role_name WHERE id = :role_id");
            $stmt->bindParam(':role_name', $formData['role_name']);
            $stmt->bindParam(':role_id', $formData['roleid']);
            $stmt->execute();

            // Extract permissions from the form data
            $permissionsReceived = $formData['permission'];

            // Get all permission columns from the permissions table
            $stmt = self::prepare("SHOW COLUMNS FROM permissions WHERE Field != 'role_id' AND Field != 'id' ");
            $stmt->execute();
            $permissionsColumns = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            // Initialize the SQL query
            $sql = "UPDATE permissions SET ";

            // Construct the SET part of the SQL query
            $setValues = [];
            foreach ($permissionsColumns as $column) {
                // Set the value based on whether the permission is received
                $setValues[] = "`$column` = :$column";
            }
            $sql .= implode(', ', $setValues);

            // Add WHERE clause to update permissions for a specific role
            $sql .= " WHERE role_id = :role_id";

            // Prepare the SQL statement
            $stmt = self::prepare($sql);

            // Bind parameters for permission values
            foreach ($permissionsColumns as $column) {
                // Set the value based on whether the permission is received
                $value = in_array($column, $permissionsReceived) ? 'Yes' : 'No';
                $stmt->bindValue(":$column", $value);
            }

            // Bind role_id parameter
            $stmt->bindParam(':role_id', $formData['roleid']);

            // Execute the SQL query
            $stmt->execute();

            $this->db->commit();

            // Return true to indicate success
            return true;
        } catch (Exception $e) {
            // Rollback the transaction on error
            $this->db->rollBack();
            // Log or handle the exception appropriately
            error_log("Error updating role and permissions: " . $e->getMessage());
            // Return false to indicate failure
            return false;
        }
    }
    public function checkDuplicateBeforeUpdate($newData, $currentDataId, $table, $refColumns, $excIdFrmTbl)
    {
        // Constructing the WHERE clause dynamically based on the reference columns
        $whereConditions = [];
        foreach ($refColumns as $column) {
            $whereConditions[] = "$column = :$column";
        }
        $whereClause = implode(" AND ", $whereConditions);

        // Constructing the SQL query
        $sql = "SELECT COUNT(*) AS count_results
            FROM $table 
            WHERE $whereClause 
            AND $excIdFrmTbl <> :current_data_id";

        // Prepare the statement
        $stmt = self::prepare($sql);

        // Bind parameters for the reference columns
        foreach ($refColumns as $column) {
            $stmt->bindParam(":$column", $newData[$column], \PDO::PARAM_STR);
        }

        // Bind parameter for the current data ID
        $stmt->bindParam(':current_data_id', $currentDataId, \PDO::PARAM_INT);

        // Execute the query
        $stmt->execute();

        // Fetch the result
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Return the count of duplicate records
        return $result['count_results'];
    }



}
