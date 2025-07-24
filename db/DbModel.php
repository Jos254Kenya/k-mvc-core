<?php


namespace sigawa\mvccore\db;

use PDO;
use sigawa\mvccore\Application;

use sigawa\mvccore\Model;

/**
 * @method void logAction(string $action, ?array $changes = null)
 */
abstract class DbModel extends Model
{

    abstract public static function tableName(): string;
    // 🔁 Hookable Lifecycle Methods
    protected function beforeSave(): void {}
    protected bool $isNewRecord = false;
    public function isAuditable(): bool
    {
        return false;
    }
    protected function afterSave(): void
    {
        if (method_exists($this, 'isAuditable') && $this->isAuditable()) {
            $classBaseName = (new \ReflectionClass($this))->getShortName(); // e.g. 'User'
            $action = $this->isNewRecord ? "A new $classBaseName created" : "$classBaseName updated";
            $this->logAction($action);
        }
    }
    protected function afterInsert(): void {}
    protected function afterUpdate(): void {}
    // BaseModel.php (or your base model class)
    public function afterFetch(): void
    {
        // Optionally override in child classes
    }

    /**
     * riginalData to store the original data before change - for logging and audit trail
     * @var array
     */
    protected array $originalData = [];
    // Optional, you can define this helper
    public static function primaryKey(): string
    {
        return 'id';
    }
    public function getPrimaryKeyValue(): mixed
    {
        return $this->{static::primaryKey()};
    }
    /**
     * Saves the current model instance to the database.
     *
     * This method determines whether to perform an INSERT (for new records)
     * or an UPDATE (for existing ones) based on whether the primary key is set.
     *
     * Lifecycle hooks:
     * - `beforeSave()` is called before either operation.
     * - `afterInsert()` is called after a successful insert.
     * - `afterUpdate()` is called after a successful update.
     * - `afterSave()` is always called after a successful save.
     *
     * @return bool True if the operation succeeded and affected rows, false otherwise.
     */
    public function save(): bool
    {
        $tableName = static::tableName();
        $attributes = $this->attributes();
        $primaryKey = static::primaryKey();
        $isNew = empty($this->{$primaryKey});
        $this->isNewRecord = $isNew;

        $this->beforeSave();

        if (!$this->isNewRecord) {
            // UPDATE LOGIC
            $setParts = [];
            $params = [];
            $replacements = [];

            foreach ($attributes as $attribute) {
                $value = $this->{$attribute};
                $placeholder = ":$attribute";

                if ($value instanceof \sigawa\mvccore\db\RawSQL) {
                    $setParts[] = "$attribute = {$value}";
                } else {
                    $setParts[] = "$attribute = $placeholder";
                    $params[$placeholder] = $value;
                }
            }

            $sql = "UPDATE $tableName SET " . implode(", ", $setParts) . " WHERE $primaryKey = :$primaryKey";
            $statement = static::prepare($sql);

            foreach ($params as $key => $value) {
                $statement->bindValue($key, $value);
            }
            $statement->bindValue(":$primaryKey", $this->{$primaryKey});

            $statement->execute();

            if ($statement->rowCount() > 0) {
                $this->afterSave();
                $this->afterUpdate();
                return true;
            }
            return false;
        } else {
            if (method_exists($this, 'validate') && !$this->validate()) {
                return false;
            }

            $columns = [];
            $placeholders = [];
            $params = [];
            $replacements = [];

            foreach ($attributes as $attribute) {
                $columns[] = $attribute;
                $placeholder = ":$attribute";
                $value = $this->{$attribute};

                if ($value instanceof \sigawa\mvccore\db\RawSQL) {
                    $placeholders[] = (string)$value;
                } else {
                    $placeholders[] = $placeholder;
                    $params[$placeholder] = $value;
                }
            }

            $sql = "INSERT INTO $tableName (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $placeholders) . ")";
            $statement = static::prepare($sql);

            foreach ($params as $key => $value) {
                $statement->bindValue($key, $value);
            }

            $statement->execute();
            $this->{$primaryKey} = Application::$app->db->lastInsertId();
            $this->isNewRecord = true;
            $this->afterSave();
            $this->afterInsert();
            $this->isNewRecord = false;
            return true;
        }
    }
    /**
     * Inserts multiple records into the database efficiently using a single query.
     *
     * @param array $records Array of associative arrays, each representing a row to insert.
     * @return int|false     Number of inserted rows on success, false on failure.
     *
     * Example:
     *   DbModel::insertMany([
     *     ['name' => 'Alice', 'email' => 'alice@example.com'],
     *     ['name' => 'Bob', 'email' => 'bob@example.com'],
     *   ]);
     */
    public static function insertMany(array $records)
    {
        if (empty($records)) {
            return false;
        }

        $tableName = static::tableName();
        $columns = array_keys($records[0]);
        $columnList = '`' . implode('`, `', $columns) . '`';

        // Build placeholders: (?, ?, ...), (?, ?, ...), ...
        $rowPlaceholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $allPlaceholders = implode(', ', array_fill(0, count($records), $rowPlaceholders));

        $sql = "INSERT INTO `$tableName` ($columnList) VALUES $allPlaceholders";
        $statement = self::prepare($sql);

        // Flatten values for all records
        $values = [];
        foreach ($records as $row) {
            foreach ($columns as $col) {
                $values[] = $row[$col] ?? null;
            }
        }

        if ($statement->execute($values)) {
            return $statement->rowCount();
        }
        return false;
    }
    /**
     * Saves and returns the model instance on success, or null on failure.
     *
     * @return static|null
     */
    public function saveAndReturn(): ?self
    {
        return $this->save() ? $this : null;
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
    public static function joinWith(
        string $joinTable,
        string $foreignKey,
        string $localKey,
        array $conditions = [],
        array $columns = ['*'],
        string $joinType = 'INNER',
        string $mainAlias = '',
        string $joinAlias = '',
        array $extra = [] // groupBy, orderBy, limit, offset
    ): array {
        $tableName = static::tableName();
        $mainAlias = $mainAlias ?: $tableName;
        $joinAlias = $joinAlias ?: $joinTable;

        $mainCols = self::getTableColumns($tableName);
        $joinCols = self::getTableColumns($joinTable);

        if (empty($mainCols) || empty($joinCols)) {
            throw new \Exception("Missing columns for '$tableName' or '$joinTable'");
        }

        $colParts = [];

        if (in_array('*', $columns)) {
            foreach ($mainCols as $col) {
                $colParts[] = "`$mainAlias`.`$col` AS `{$mainAlias}_$col`";
            }
            foreach ($joinCols as $col) {
                $colParts[] = "`$joinAlias`.`$col` AS `{$joinAlias}_$col`";
            }
        } else {
            foreach ($columns as $column) {
                if (strpos($column, '.') !== false) {
                    [$alias, $col] = explode('.', $column);
                    $colParts[] = "`$alias`.`$col` AS `{$alias}_$col`";
                }
            }
        }

        $sql = "SELECT " . implode(", ", $colParts);
        $sql .= " FROM `$tableName` AS `$mainAlias`";
        $sql .= " $joinType JOIN `$joinTable` AS `$joinAlias` ON `$mainAlias`.`$localKey` = `$joinAlias`.`$foreignKey`";

        if ($conditions) {
            $clauses = [];
            foreach ($conditions as $key => $val) {
                if (strpos($key, '.') !== false) {
                    [$alias, $col] = explode('.', $key);
                    $clauses[] = "`$alias`.`$col` = :{$alias}_{$col}";
                } else {
                    $clauses[] = "`$mainAlias`.`$key` = :{$mainAlias}_{$key}";
                }
            }
            $sql .= " WHERE " . implode(' AND ', $clauses);
        }

        // Extra: groupBy, orderBy, limit, offset
        if (!empty($extra['groupBy'])) {
            $sql .= " GROUP BY " . $extra['groupBy'];
        }

        if (!empty($extra['orderBy'])) {
            $orderClauses = [];
            foreach ($extra['orderBy'] as $col => $dir) {
                $orderClauses[] = "$col $dir";
            }
            $sql .= " ORDER BY " . implode(', ', $orderClauses);
        }

        if (!empty($extra['limit'])) {
            $sql .= " LIMIT " . intval($extra['limit']);
        }

        if (!empty($extra['offset'])) {
            $sql .= " OFFSET " . intval($extra['offset']);
        }

        $stmt = self::prepare($sql);
        foreach ($conditions as $key => $val) {
            if (strpos($key, '.') !== false) {
                [$alias, $col] = explode('.', $key);
                $stmt->bindValue(":{$alias}_{$col}", $val);
            } else {
                $stmt->bindValue(":{$mainAlias}_{$key}", $val);
            }
        }

        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function joinMultiple(array $joins, array $conditions = [], array $columns = ['*'], array $extra = []): array
    {
        $baseTable = static::tableName();
        $baseAlias = $joins[0]['mainAlias'] ?? $baseTable;
        $sql = "SELECT ";
        $columnSelections = [];

        // Fetch columns from each table
        $allTables = [$baseTable];
        $tableColumns = [$baseAlias => self::getTableColumns($baseTable)];

        foreach ($joins as $join) {
            $tbl = $join['table'];
            $alias = $join['alias'] ?? $tbl;
            $tableColumns[$alias] = self::getTableColumns($tbl);
            $allTables[] = $tbl;
        }

        if (in_array('*', $columns)) {
            foreach ($tableColumns as $alias => $cols) {
                foreach ($cols as $col) {
                    $columnSelections[] = "`$alias`.`$col` AS `{$alias}_$col`";
                }
            }
        } else {
            foreach ($columns as $col) {
                if (strpos($col, '.') !== false) {
                    [$alias, $colName] = explode('.', $col);
                    $columnSelections[] = "`$alias`.`$colName` AS `{$alias}_$colName`";
                }
            }
        }

        $sql .= implode(", ", $columnSelections);
        $sql .= " FROM `$baseTable` AS `$baseAlias`";

        foreach ($joins as $join) {
            $joinType = strtoupper($join['type'] ?? 'INNER');
            $tbl = $join['table'];
            $alias = $join['alias'] ?? $tbl;
            $onLocal = $join['localKey'];
            $onForeign = $join['foreignKey'];

            $sql .= " $joinType JOIN `$tbl` AS `$alias` ON `$join.['mainAlias']`.`$onLocal` = `$alias`.`$onForeign`";
        }

        if ($conditions) {
            $clauses = [];
            foreach ($conditions as $key => $val) {
                if (strpos($key, '.') !== false) {
                    [$alias, $col] = explode('.', $key);
                    $clauses[] = "`$alias`.`$col` = :{$alias}_{$col}";
                }
            }
            $sql .= " WHERE " . implode(" AND ", $clauses);
        }

        // Handle extras
        if (!empty($extra['groupBy'])) {
            $sql .= " GROUP BY " . $extra['groupBy'];
        }

        if (!empty($extra['orderBy'])) {
            $orderClauses = [];
            foreach ($extra['orderBy'] as $col => $dir) {
                $orderClauses[] = "$col $dir";
            }
            $sql .= " ORDER BY " . implode(', ', $orderClauses);
        }

        if (!empty($extra['limit'])) {
            $sql .= " LIMIT " . intval($extra['limit']);
        }

        if (!empty($extra['offset'])) {
            $sql .= " OFFSET " . intval($extra['offset']);
        }

        $stmt = self::prepare($sql);
        foreach ($conditions as $key => $val) {
            [$alias, $col] = explode('.', $key);
            $stmt->bindValue(":{$alias}_{$col}", $val);
        }

        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function findAllByQuery(string $query, array $params = [])
    {
        $statement = self::prepare($query);

        foreach ($params as $key => $value) {
            $statement->bindValue(":$key", $value);
        }

        $statement->execute();
        return $statement->fetchAll(\PDO::FETCH_OBJ); // Fetch all as object
    }
    /**
     * Converts the model's public properties to an associative array.
     * @return array
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
    public function afterFind(): void
    {
        $this->originalData = $this->toArray();
    }

    public function getChanges(): array
    {
        $old = $this->originalData;
        $new = $this->toArray();

        $diffOld = [];
        $diffNew = [];

        foreach ($new as $key => $value) {
            if (!array_key_exists($key, $old) || $old[$key] !== $value) {
                $diffOld[$key] = $old[$key] ?? null;
                $diffNew[$key] = $value;
            }
        }

        return [
            'old' => $diffOld,
            'new' => $diffNew
        ];
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

    public function update(array $data): bool
    {
        $tableName = static::tableName();
        $primaryKey = static::primaryKey();

        if (empty($this->{$primaryKey})) {
            throw new \Exception("Cannot update record without a primary key value.");
        }

        $setClause = [];
        foreach ($data as $key => $value) {
            $setClause[] = "`$key` = :$key";
        }

        $sql = "UPDATE `$tableName` SET " . implode(", ", $setClause) . " WHERE `$primaryKey` = :primaryKey";
        $statement = self::prepare($sql);

        foreach ($data as $key => $value) {
            $statement->bindValue(":$key", $value);
        }
        $statement->bindValue(":primaryKey", $this->{$primaryKey});

        return $statement->execute();
    }

    public static function buildWhere(array $conditions): string
    {
        if (empty($conditions)) {
            return '';
        }

        $clauses = [];
        foreach ($conditions as $key => $value) {
            if (is_array($value)) {
                // Handle IN clause
                $placeholders = implode(", ", array_map(fn($i) => ":{$key}_$i", array_keys($value)));
                $clauses[] = "`$key` IN ($placeholders)";
            } elseif (strpos($key, ' ') !== false) {
                // Handle operators like `>=`, `<=`, `!=`, etc.
                $clauses[] = "$key :$key";
            } else {
                // Default equality condition
                $clauses[] = "`$key` = :$key";
            }
        }

        return "WHERE " . implode(" AND ", $clauses);
    }

    /**
     * Fluent query builder for flexible SELECT queries.
     * Usage:
     *   $query = Product::query();
     *   $query->where('price', '>=', 100)->where('brand', 'Acme')->all();
     */
    public static function query()
    {
        return new BaseQueryBuilder(static::class);
    }

    public static function all(
        array $filters = [],
        array $columns = ['*'],
        array $extra = [],
        array $rawConditions = [],
        array $bindings = []
    ): array {
        $tableName = static::tableName();
        $columnSelections = $columns === ['*']
            ? '*'
            : implode(", ", array_map(fn($col) => "`$col`", $columns));

        $sql = "SELECT $columnSelections FROM `$tableName`";

        $whereParts = [];

        // Add key-value filters
        foreach ($filters as $key => $value) {
            $param = ":" . str_replace('.', '_', $key); // for table.column
            $whereParts[] = "`$key` = $param";
            $bindings[$param] = $value;
        }

        // Add raw conditions like "latitude IS NOT NULL"
        foreach ($rawConditions as $condition) {
            $whereParts[] = $condition;
        }

        if (!empty($whereParts)) {
            $sql .= " WHERE " . implode(" AND ", $whereParts);
        }

        // GROUP BY
        if (!empty($extra['groupBy'])) {
            $sql .= " GROUP BY " . $extra['groupBy'];
        }

        // ORDER BY
        if (!empty($extra['orderBy'])) {
            $orderClauses = [];
            foreach ($extra['orderBy'] as $col => $dir) {
                $orderClauses[] = "`$col` $dir";
            }
            $sql .= " ORDER BY " . implode(", ", $orderClauses);
        }

        // LIMIT & OFFSET
        if (!empty($extra['limit'])) {
            $sql .= " LIMIT " . intval($extra['limit']);
        }

        if (!empty($extra['offset'])) {
            $sql .= " OFFSET " . intval($extra['offset']);
        }

        $statement = self::prepare($sql);

        // Bind all parameters
        foreach ($bindings as $key => $value) {
            $statement->bindValue($key, $value);
        }

        $statement->execute();
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function hydrate(array $data): static
    {
        $instance = new static();
        $fillable = $instance->attributes();
        foreach ($data as $key => $value) {
            if (in_array($key, $fillable)) {
                $instance->$key = $value;
            }
        }
        return $instance;
    }

    /**
     * Fluent query builder for SELECT statements.
     * Usage:
     *   VendorProduct::select(['col1', 'col2'])->where(['id' => 1])->all();
     */

    public static function select(array $columns = ['*'])
    {
        return new class(static::tableName(), $columns, static::class) {
            private string $table;
            private array $columns;
            private array $where = [];
            private array $params = [];
            private ?string $orderBy = null;
            private ?int $limit = null;
            private ?int $offset = null;
            private string $modelClass;

            public function __construct($table, $columns, $modelClass)
            {
                $this->table = $table;
                $this->columns = $columns;
                $this->modelClass = $modelClass;
            }
            private array $joins = [];

            public function join(string $table, string $on, string $type = 'INNER')
            {
                $this->joins[] = strtoupper($type) . " JOIN `$table` ON $on";
                return $this;
            }
            public function where(string|array $conditions, array $params = [])
            {
                return $this->addCondition('AND', $conditions, $params);
            }

            public function andWhere(string|array $conditions, array $params = [])
            {
                return $this->addCondition('AND', $conditions, $params);
            }

            public function orWhere(string|array $conditions, array $params = [])
            {
                return $this->addCondition('OR', $conditions, $params);
            }

            private function addCondition(string $type, string|array $conditions, array $params): self
            {
                $clause = '';

                if (is_array($conditions)) {
                    $clauses = [];
                    foreach ($conditions as $key => $value) {
                        $paramKey = str_replace('.', '_', $key);
                        $placeholder = ":{$paramKey}_" . count($this->params);
                        $clauses[] = "`$key` = $placeholder";
                        $this->params[$placeholder] = $value;
                    }

                    $clause = implode(" $type ", $clauses);
                } else {
                    $clause = $conditions;
                    foreach ($params as $key => $value) {
                        $this->params[":$key"] = $value;
                    }
                }

                if (!empty($this->where)) {
                    $this->where[] = $type . ' ' . $clause;
                } else {
                    $this->where[] = $clause;
                }

                return $this;
            }

            public function orderBy(string $column, string $direction = 'ASC')
            {
                $this->orderBy = "`$column` " . (strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC');
                return $this;
            }

            public function limit(int $limit)
            {
                $this->limit = $limit;
                return $this;
            }

            public function offset(int $offset)
            {
                $this->offset = $offset;
                return $this;
            }

            public function all(bool $asArray = false): array
            {
                $sql = "SELECT " . implode(', ', array_map(function ($col) {
                    return preg_match('/[^\w.]/', $col) ? $col : "`$col`";
                }, $this->columns));

                if (!empty($this->joins)) {
                    $sql .= ' ' . implode(' ', $this->joins);
                }

                if (!empty($this->where)) {
                    $sql .= " WHERE " . implode(" ", $this->where);
                }

                if ($this->orderBy) {
                    $sql .= " ORDER BY " . $this->orderBy;
                }

                if ($this->limit !== null) {
                    $sql .= " LIMIT " . intval($this->limit);
                }

                if ($this->offset !== null) {
                    $sql .= " OFFSET " . intval($this->offset);
                }

                $stmt = $this->modelClass::prepare($sql);
                foreach ($this->params as $key => $val) {
                    $stmt->bindValue($key, $val);
                }

                $stmt->execute();
                $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                if ($asArray) {
                    return $results;
                }

                return array_map(fn($row) => $this->modelClass::hydrate($row), $results);
            }

            public function first(bool $asArray = false): ?object
            {
                $this->limit = 1;
                $results = $this->all($asArray);
                return $results[0] ?? null;
            }
        };
    }


    /**
     * Starts a fluent query builder for the model.
     * Usage:
     *   FacilityStock::find()->where([...])->joinWith([...])->all();
     */
    public static function find(): object
    {
        $modelClass = static::class;
        return new class($modelClass) {
            private string $modelClass;
            private string $table;
            private array $select = ['*'];
            private array $joins = [];
            private array $where = [];
            private array $params = [];
            private array $orderBy = [];
            private ?int $limit = null;
            private ?int $offset = null;

            public function __construct($modelClass)
            {
                $this->modelClass = $modelClass;
                $this->table = $modelClass::tableName();
            }

            public function select(array $columns)
            {
                $this->select = $columns;
                return $this;
            }

            /**
             * Accepts joinWith(['relationName', ...]) or joinWith('relationName')
             */
            public function joinWith(array|string $relations)
            {
                $relations = (array)$relations;
                foreach ($relations as $relation) {
                    // Get relation definition from model
                    $instance = new $this->modelClass();
                    $relationsDef = method_exists($instance, 'relations') ? $instance->relations() : [];
                    if (!isset($relationsDef[$relation])) {
                        throw new \Exception("Relation '$relation' not defined in {$this->modelClass}");
                    }
                    [$type, $relatedClass, $foreignKey, $localKey] = $relationsDef[$relation];
                    $relatedTable = $relatedClass::tableName();
                    $alias = $relation;

                    // Only support HAS_ONE/BELONGS_TO/HAS_MANY for now
                    $this->joins[] = [
                        'table' => $relatedTable,
                        'alias' => $alias,
                        'on' => "`{$this->table}`.`$localKey` = `$alias`.`$foreignKey`"
                    ];
                }
                return $this;
            }

            public function where(array $conditions)
            {
                foreach ($conditions as $key => $value) {
                    $param = ':' . str_replace('.', '_', $key) . count($this->params);
                    if (strpos($key, '.') !== false) {
                        [$alias, $col] = explode('.', $key, 2);
                        $this->where[] = "`$alias`.`$col` = $param";
                    } else {
                        $this->where[] = "`{$this->table}`.`$key` = $param";
                    }
                    $this->params[$param] = $value;
                }
                return $this;
            }

            public function andWhere(array $conditions)
            {
                return $this->where($conditions);
            }

            public function orderBy(array $columns)
            {
                foreach ($columns as $key => $dir) {
                    $direction = (is_string($dir) && strtoupper($dir) === 'DESC') || $dir === SORT_DESC ? 'DESC' : 'ASC';
                    if (strpos($key, '.') !== false) {
                        [$alias, $col] = explode('.', $key, 2);
                        $this->orderBy[] = "`$alias`.`$col` $direction";
                    } else {
                        $this->orderBy[] = "`{$this->table}`.`$key` $direction";
                    }
                }
                return $this;
            }

            public function limit(int $limit)
            {
                $this->limit = $limit;
                return $this;
            }

            public function offset(int $offset)
            {
                $this->offset = $offset;
                return $this;
            }

            public function all(): array
            {
                $sql = $this->buildSql();
                $stmt = $this->modelClass::prepare($sql);
                foreach ($this->params as $key => $val) {
                    $stmt->bindValue($key, $val);
                }
                $stmt->execute();
                return $stmt->fetchAll(\PDO::FETCH_CLASS, $this->modelClass);
            }

            public function one(): ?object
            {
                $this->limit = 1;
                $results = $this->all();
                return $results[0] ?? null;
            }

            private function buildSql(): string
            {
                $select = $this->select === ['*']
                    ? "{$this->table}.*"
                    : implode(', ', array_map(function ($col) {
                        if (strpos($col, '.') !== false) {
                            [$alias, $c] = explode('.', $col, 2);
                            return "`$alias`.`$c`";
                        }
                        return "`{$this->table}`.`$col`";
                    }, $this->select));

                $sql = "SELECT $select FROM `{$this->table}`";

                foreach ($this->joins as $join) {
                    $sql .= " LEFT JOIN `{$join['table']}` AS `{$join['alias']}` ON {$join['on']}";
                }

                if ($this->where) {
                    $sql .= " WHERE " . implode(' AND ', $this->where);
                }

                if ($this->orderBy) {
                    $sql .= " ORDER BY " . implode(', ', $this->orderBy);
                }

                if ($this->limit !== null) {
                    $sql .= " LIMIT " . intval($this->limit);
                }
                if ($this->offset !== null) {
                    $sql .= " OFFSET " . intval($this->offset);
                }

                return $sql;
            }
        };
    }
    public static function findbyId($id): ?self
    {
        $tableName = static::tableName();
        $primaryKey = static::primaryKey();

        $sql = "SELECT * FROM `$tableName` WHERE `$primaryKey` = :id LIMIT 1";
        $statement = self::prepare($sql);
        $statement->bindValue(':id', $id);
        $statement->execute();

        $result = $statement->fetchObject(static::class);
        return $result !== false ? $result : null;
    }
    public static function create(array $data): ?self
    {
        $tableName = static::tableName();
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ":$col", $columns);

        $sql = "INSERT INTO `$tableName` (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $placeholders) . ")";
        $statement = self::prepare($sql);

        foreach ($data as $key => $value) {
            $statement->bindValue(":$key", $value);
        }

        if ($statement->execute()) {
            $newRecord = new static();
            $primaryKey = static::primaryKey();
            $newRecord->{$primaryKey} = Application::$app->db->lastInsertId();

            foreach ($data as $key => $value) {
                $newRecord->{$key} = $value;
            }

            return $newRecord;
        }

        return null;
    }
    public static function findAllWhere(string $condition, array $params = []): array
    {
        $tableName = static::tableName();
        $sql = "SELECT * FROM `$tableName` WHERE $condition";
        $statement = self::prepare($sql);

        foreach ($params as $key => $value) {
            $paramKey = is_int($key) ? $key + 1 : $key; // Handle positional or named parameters
            $statement->bindValue(is_int($key) ? $paramKey : ":$key", $value);
        }

        $statement->execute();
        return $statement->fetchAll(\PDO::FETCH_CLASS, static::class);
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
    /**
     * Define model relationships (Laravel-lite style).
     * Override in child models to declare relationships.
     * Example:
     *   return [
     *     'posts' => [self::HAS_MANY, Post::class, 'user_id', 'id'],
     *     'profile' => [self::HAS_ONE, Profile::class, 'user_id', 'id'],
     *     'roles' => [self::BELONGS_TO_MANY, Role::class, 'user_role', 'user_id', 'role_id'],
     *   ];
     * @return array
     */
    public function relations(): array
    {
        return [];
    }

    /**
     * Returns an array of attributes that should be cast to/from JSON.
     * Override in child models.
     * Example: return ['settings', 'meta'];
     * @return array
     */
    public static function jsonable(): array
    {
        return [];
    }

    /**
     * Returns an array of attribute casts.
     * Example: return ['is_active' => 'bool', 'price' => 'float', 'created_at' => 'datetime'];
     * Supported: int, float, bool, string, array, json, datetime
     * @return array
     */
    public static function casts(): array
    {
        return [];
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
    public static function findOne($where, $orderBy = null)
    {
        $tableName = static::tableName();
        $conditions = [];
        $params = [];

        foreach ($where as $key => $value) {
            if (is_null($value)) {
                $conditions[] = "$key IS NULL";
            } else {
                $conditions[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        $whereClause = implode(" AND ", $conditions);
        $orderClause = $orderBy ? " ORDER BY $orderBy" : "";

        $sql = "SELECT * FROM $tableName WHERE $whereClause{$orderClause} LIMIT 1";
        $statement = self::prepare($sql);

        foreach ($params as $param => $value) {
            $statement->bindValue($param, $value);
        }

        $statement->execute();
        $result = $statement->fetchObject(static::class);
        if ($result !== false && method_exists($result, 'afterFetch')) {
            $result->afterFetch();
        }
        return $result !== false ? $result : null;
    }
    /**
     * Finds all records matching the given conditions, with optional ordering.
     *
     * @param array $where   Associative array of conditions.
     * @param string|null $orderBy Column name to order by.
     * @param string $direction ASC or DESC.
     * @return static[]
     */
    public static function findAll(array $where = [], ?string $orderBy = null, string $direction = 'ASC')
    {
        $tableName = static::tableName();
        $sql = "SELECT * FROM $tableName";

        if (!empty($where)) {
            $attributes = array_keys($where);
            $conditions = implode(" AND ", array_map(fn($attr) => "$attr = :$attr", $attributes));
            $sql .= " WHERE $conditions";
        }

        if ($orderBy !== null) {
            $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            $sql .= " ORDER BY `$orderBy` $direction";
        }
        $statement = self::prepare($sql);
        foreach ($where as $key => $value) {
            $statement->bindValue(":$key", $value);
        }
        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_CLASS, static::class);
        foreach ($results as $item) {
            if (method_exists($item, 'afterFetch')) {
                $item->afterFetch();
            }
        }
        return $results;
    }

    /**
     * Fetches a limited set of records with an optional offset for pagination or batch processing.
     *
     * @param int $limit  Number of records to fetch.
     * @param int $offset Offset from where to begin fetching.
     * @return static[]   Array of model instances.
     */
    public static function findWithLimitOffset(int $limit, int $offset = 0): array
    {
        $tableName = static::tableName();
        $sql = "SELECT * FROM `$tableName` LIMIT :limit OFFSET :offset";
        $statement = self::prepare($sql);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_CLASS, static::class);
    }
    /**
     * Inserts multiple records into the database.
     *
     * @param array $records Array of associative arrays where each item represents a row.
     * @return bool          True on success, false on failure.
     */
    public static function createMany(array $records): bool
    {
        if (empty($records)) {
            return false;
        }

        $tableName = static::tableName();
        $columns = array_keys($records[0]);
        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $allPlaceholders = implode(', ', array_fill(0, count($records), $placeholders));

        $sql = "INSERT INTO `$tableName` (`" . implode('`, `', $columns) . "`) VALUES $allPlaceholders";
        $statement = self::prepare($sql);

        $flatValues = [];
        foreach ($records as $row) {
            foreach ($columns as $column) {
                $flatValues[] = $row[$column] ?? null;
            }
        }

        return $statement->execute($flatValues);
    }
    /**
     * Counts the number of records in the table matching the given conditions.
     *
     * @param array $where Optional associative array of conditions.
     * @return int         Number of matching records.
     */
    public static function count(array $where = []): int
    {
        $tableName = static::tableName();
        $sql = "SELECT COUNT(*) as count FROM `$tableName`";

        if (!empty($where)) {
            $conditions = implode(' AND ', array_map(fn($col) => "$col = :$col", array_keys($where)));
            $sql .= " WHERE $conditions";
        }

        $statement = self::prepare($sql);
        foreach ($where as $key => $value) {
            $statement->bindValue(":$key", $value);
        }
        $statement->execute();
        return (int) $statement->fetchColumn();
    }
    /**
     * Checks if a record exists that matches the provided conditions.
     *
     * @param array $where Associative array of column => value.
     * @return bool        True if record exists, false otherwise.
     */
    public static function exists(array $where): bool
    {
        return static::count($where) > 0;
    }
    /**
     * Updates records in the database matching the given conditions with the provided data.
     *
     * @param array $conditions Associative array for WHERE clause.
     * @param array $data       Associative array for SET clause.
     * @return bool             True on success, false on failure.
     */
    public static function updateWhere(array $conditions, array $data): bool
    {
        if (empty($conditions) || empty($data)) return false;

        $tableName = static::tableName();
        $setClause = implode(', ', array_map(fn($col) => "`$col` = :set_$col", array_keys($data)));
        $whereClause = implode(' AND ', array_map(fn($col) => "`$col` = :where_$col", array_keys($conditions)));

        $sql = "UPDATE `$tableName` SET $setClause WHERE $whereClause";
        $statement = self::prepare($sql);

        foreach ($data as $key => $value) {
            $statement->bindValue(":set_$key", $value);
        }

        foreach ($conditions as $key => $value) {
            $statement->bindValue(":where_$key", $value);
        }

        return $statement->execute();
    }
    /**
     * Retrieves the first record in the table, optionally ordered by a specific column.
     *
     * @param string|null $orderBy Column name to order by (defaults to primary key).
     * @return static|null         The first record or null if none found.
     */
    public static function first(?string $orderBy = null): ?self
    {
        $tableName = static::tableName();
        $primaryKey = static::primaryKey();
        $orderColumn = $orderBy ?? $primaryKey;

        $sql = "SELECT * FROM `$tableName` ORDER BY `$orderColumn` ASC LIMIT 1";
        $statement = self::prepare($sql);
        $statement->execute();

        $result = $statement->fetchObject(static::class);
        return $result !== false ? $result : null;
    }
    /**
     * Retrieves the last record in the table, optionally ordered by a specific column.
     *
     * @param string|null $orderBy Column name to order by (defaults to primary key).
     * @return static|null         The last record or null if none found.
     */
    public static function last(?string $orderBy = null): ?self
    {
        $tableName = static::tableName();
        $primaryKey = static::primaryKey();
        $orderColumn = $orderBy ?? $primaryKey;

        $sql = "SELECT * FROM `$tableName` ORDER BY `$orderColumn` DESC LIMIT 1";
        $statement = self::prepare($sql);
        $statement->execute();

        $result = $statement->fetchObject(static::class);
        return $result !== false ? $result : null;
    }
    /**
     * Retrieves a paginated set of records from the table.
     *
     * @param int $page      Current page number (1-based).
     * @param int $perPage   Number of records per page.
     * @param array $filters Optional filtering conditions.
     * @param string|null $orderBy Column to order results by.
     * @param string $direction ASC or DESC ordering.
     * @return array{data: static[], total: int, page: int, perPage: int, lastPage: int}
     */
    public static function paginate(int $page = 1, int $perPage = 10, array $filters = [], ?string $orderBy = null, string $direction = 'ASC'): array
    {
        $offset = ($page - 1) * $perPage;
        $tableName = static::tableName();
        $orderColumn = $orderBy ?? static::primaryKey();

        $whereClause = '';
        $parameters = [];
        if (!empty($filters)) {
            $clauses = [];
            foreach ($filters as $column => $value) {
                $clauses[] = "`$column` = :$column";
                $parameters[":$column"] = $value;
            }
            $whereClause = "WHERE " . implode(' AND ', $clauses);
        }

        $countSql = "SELECT COUNT(*) FROM `$tableName` $whereClause";
        $countStmt = self::prepare($countSql);
        foreach ($parameters as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $dataSql = "SELECT * FROM `$tableName` $whereClause ORDER BY `$orderColumn` $direction LIMIT :limit OFFSET :offset";
        $dataStmt = self::prepare($dataSql);
        foreach ($parameters as $key => $value) {
            $dataStmt->bindValue($key, $value);
        }
        $dataStmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $dataStmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $dataStmt->execute();
        $data = $dataStmt->fetchAll(\PDO::FETCH_CLASS, static::class);

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => (int) ceil($total / $perPage),
        ];
    }
    /**
     * Performs a basic search using LIKE on specified columns.
     *
     * @param string $term       Search keyword.
     * @param array $columns     Columns to search within.
     * @param int|null $limit    Optional result limit.
     * @return static[]          Array of matching records.
     */
    public static function search(string $term, array $columns, ?int $limit = null): array
    {
        if (empty($columns)) return [];

        $tableName = static::tableName();
        $likeClauses = implode(' OR ', array_map(fn($col) => "`$col` LIKE :term", $columns));
        $sql = "SELECT * FROM `$tableName` WHERE $likeClauses";

        if ($limit !== null) {
            $sql .= " LIMIT :limit";
        }

        $statement = self::prepare($sql);
        $statement->bindValue(':term', '%' . $term . '%');

        if ($limit !== null) {
            $statement->bindValue(':limit', $limit, \PDO::PARAM_INT);
        }

        $statement->execute();
        return $statement->fetchAll(\PDO::FETCH_CLASS, static::class);
    }
}
