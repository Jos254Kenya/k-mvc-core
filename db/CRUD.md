## CRUD class (extends Database class)
### saveGeneric function
The saveGeneric method is responsible for inserting data into a specified table in the database.
It accepts three parameters:
* $tableName: The name of the table where data will be inserted.
* $attributes: An array containing the column names of the table.
* $data: An associative array where keys are column names, and values are the corresponding data to be saved.

The method constructs an SQL INSERT statement dynamically using the provided table name and attributes.
It iterates over the attributes and binds the corresponding data from the $data array to the prepared statement using named placeholders.
Before binding the values, the method checks if data exists for each attribute in the $data array. If data is missing for any attribute, it throws an InvalidArgumentException.

Finally, the method executes the prepared statement and returns true if the insertion is successful, false otherwise.
This implementation offers flexibility and decoupling, allowing data to be saved to any table with any set of attributes and data. It's suitable for scenarios where the data to be saved is not directly associated with the current object's state.

* Example Usage
```php
// Example usage of the save method
$attributes = ['name', 'email', 'age'];
$data = ['name' => 'John Doe', 'email' => 'john@example.com', 'age' => 30];
$tableName = 'users';

try {
    // Assuming $db is an instance of a database class with the save method
    $success = $db->saveGeneric($tableName, $attributes, $data);

    if ($success) {
        echo "Data saved successfully.";
    } else {
        echo "Failed to save data.";
    }
} catch (\InvalidArgumentException $e) {
    echo "Error: " . $e->getMessage();
}

```
In this example:

* We define an array [$attributes] containing the column names of the users table.
* We create an associative array $data where keys are column names and values are the corresponding data to be saved.
* We specify the table name 'users'.
* We call the save method on the database instance [$db], passing the table name, attributes, and data.
* If the data is successfully saved, it prints "Data saved successfully." Otherwise, it prints "Failed to save data."
* If the $data array is missing data for any attribute specified in $attributes, an InvalidArgumentException is thrown, and an error message is displayed.
* This example demonstrates how to use the saveGeneric method to insert data into a database table, providing the flexibility to specify attributes and data dynamically without relying on the current object's state.

# fetchAll function Example usage
```php
// Assume $db is an instance of your database class that contains the fetchAll method

// Example 1: Fetch all rows from a single table with no conditions
$table = 'users';
$columns = '*';
$users = $db->fetchAll($table, $columns);
// $users now contains an array of associative arrays representing the rows in the 'users' table

// Example 2: Fetch specific columns from multiple tables with conditions
$tables = ['orders', 'customers'];
$columns = 'orders.*, customers.name AS customer_name';
$conditions = ['orders.customer_id' => 1, 'orders.status' => 'pending'];
$orderData = $db->fetchAll($tables, $columns, $conditions);
// $orderData now contains an array of associative arrays representing the matching rows from the 'orders' and 'customers' tables

// Example 3: Fetch all rows from a table with conditions
$table = 'products';
$columns = '*';
$conditions = ['category' => 'Electronics', 'price' => ['min' => 100, 'max' => 500]];
$productData = $db->fetchAll($table, $columns, $conditions);
// $productData now contains an array of associative arrays representing the rows in the 'products' table that meet the specified conditions

```
# update method
Documentation and Improvements:

The update method updates rows in a specified table based on provided data and conditions.
* It accepts three parameters:
*  $table: The name of the table to update.
* $data: An associative array of column-value pairs to be updated.
* $conditions: An associative array of conditions to match rows for updating.

It constructs a dynamic UPDATE SQL statement based on the provided table name, data, and conditions.

Data values are bound to named placeholders in the SQL statement using bindValue.

Condition values are also bound to named placeholders with a prefix to avoid conflicts with data placeholders.

After executing the prepared statement, the method returns true if at least one row is affected, indicating a successful update; otherwise, it returns false

1. Documentation: Added inline documentation using PHPDoc comments to explain the purpose and parameters of the method.

2. Error Handling: Added a try-catch block to catch potential PDOException exceptions that might occur during the database operation. This provides better error handling and prevents uncaught exceptions.

3. Readability: Improved variable names and added comments for better readability and understanding of the code.

4. Type Hinting: Added type hints for parameters to enforce data types and improve code clarity.

5. Return Value: The method now returns true if at least one row is affected by the update operation, providing better feedback on the success of the update.

This improved version ensures that the update method is more robust, readable, and maintainable. It also provides better error handling and documentation for future maintenance and debugging.
# delete method
Explanation and Usage:

In the updated delete method, each condition in the $conditions array can include comparison operators (<, >, <=, >=) to provide more flexibility in specifying conditions.
* You can use this method with various conditions, such as 'id' => 1 or 'age >=' => 30.
When specifying conditions with comparison operators, make sure to separate the column name and operator by a space, as shown in the examples.
* The method constructs a dynamic DELETE query based on the provided table name and conditions, handling comparison operators appropriately.
* It prepares the SQL statement, binds parameters if conditions are provided, and executes the query.
* The method returns true if the deletion is successful, false otherwise.

You can now use the delete method with a wider range of conditions, including those involving comparison operators, to delete data from the specified table based on your requirements

Example Usage

```php
// Example 1: Deleting data based on a single condition
$tableName = 'users';
$conditions = ['id' => 5];

try {
    // Assuming $db is an instance of a database class with the delete method
    $success = $db->delete($tableName, $conditions);
    
    if ($success) {
        echo "Data deleted successfully based on condition.";
    } else {
        echo "Failed to delete data based on condition.";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Example 2: Deleting data based on multiple conditions with comparison operators
$tableName = 'users';
$conditions = ['age >=' => 30, 'status' => 'inactive'];

try {
    // Assuming $db is an instance of a database class with the delete method
    $success = $db->delete($tableName, $conditions);
    
    if ($success) {
        echo "Data deleted successfully based on conditions with comparison operators.";
    } else {
        echo "Failed to delete data based on conditions with comparison operators.";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}

```
Explanation of the example

In these examples:

Example 1 demonstrates deleting data from the 'users' table where the id column equals 5. 
It calls the delete method with a single condition array ['id' => 5].

Example 2 illustrates deleting data from the 'users' table where the age column is greater than or equal to 30 and the status column is 'inactive'. 
It calls the delete method with a condition array ['age >=' => 30, 'status' => 'inactive'].

Both examples handle potential exceptions that might occur during the deletion process. The try-catch blocks ensure that any errors are properly handled and appropriate messages are displayed to the user.
