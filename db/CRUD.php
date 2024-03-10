<?php


namespace Merudairy\Fmmerudairy\core\db;

use PDOException;

class CRUD extends Database
{
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
        // Prepare the SQL statement
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
    /**
     * @param $table
     * @param $data
     * @param $conditions
     * @return bool
     */


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
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
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
    public function readTableAdvanced(string $table, array $fields, array $conditions = [], string $joins = '', int $limit = null): array
    {
        // Construct the SELECT query dynamically
        $query = 'SELECT ' . implode(', ', $fields) . ' FROM ' . $table;
        // Handle JOIN clauses
        if (!empty($joins)) {
            $query .= ' ' . $joins;
        }
        // Construct the WHERE clause if conditions are provided
        $whereClause = '';
        if (!empty($conditions)) {
            $whereClause = ' WHERE ' . implode(' AND ', array_map(function ($key) {
                    return "$key = :$key";
                }, array_keys($conditions)));
        }
        $query .= $whereClause;
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
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Updates data in the specified table based on the provided data and conditions.
     *
     * @param string $table The name of the table to update data in.
     * @param array $data An associative array of columns and their new values to be updated.
     * @param array $conditions An associative array of conditions for the update operation.
     * @return bool True if the update is successful, false otherwise.
     */
    public function update(string $table, array $data, array $conditions): bool
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
            // Return true if at least one row is affected
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            // Log or handle the exception appropriately
            error_log("Error updating data: " . $e->getMessage());
            return false;
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

    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }
}