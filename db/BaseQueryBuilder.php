<?php

namespace sigawa\mvccore\db;

use PDO;
use sigawa\mvccore\Application;

class BaseQueryBuilder
{
    protected string $table;
    protected string $alias = '';
    protected array $select = [];
    protected array $joins = [];
    protected array $where = [];
    protected array $bindings = [];
    protected string $orderBy = '';
    protected ?int $limit = null;
    protected ?int $offset = null;
    protected string $groupBy = '';
    protected string $having = '';
    protected string $modelClass;
    protected bool $isDistinct = false;
    protected ?string $distinctColumn = null;
    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
        $this->table = $modelClass::tableName();
    }

    public function from(string $tableWithAlias): self
    {
        if (preg_match('/^(\w+)\s+(?:as\s+)?(\w+)$/i', $tableWithAlias, $matches)) {
            $this->table = $matches[1];
            $this->alias = $matches[2];
        } else {
            $this->table = $tableWithAlias;
            $this->alias = '';
        }
        return $this;
    }

    public function select(array $columns): self
    {
        $this->select = $columns;
        return $this;
    }

    public function filterByArray(array $filters): self
    {
        foreach ($filters as $col => $val) {
            if (is_array($val)) {
                $this->where($col, $val[0], $val[1]); // e.g. ['>=', 50]
            } else {
                $this->where($col, '=', $val);
            }
        }
        return $this;
    }

    public function join(string $tableWithAlias, string $left, string $operator, string $right): self
    {
        $this->joins[] = "JOIN {$tableWithAlias} ON {$left} {$operator} {$right}";
        return $this;
    }

    public function where(string $column, string $operator, $value): self
    {
        $param = ':param' . count($this->bindings);
        $this->bindings[$param] = $value;
        $this->where[] = "{$column} {$operator} {$param}";
        return $this;
    }
    public function orWhere(string $column, string $operator, $value): self
    {
        $param = ':param' . count($this->bindings);
        $this->bindings[$param] = $value;
        if (empty($this->where)) {
            $this->where[] = "{$column} {$operator} {$param}";
        } else {
            $last = array_pop($this->where);
            $this->where[] = "({$last} OR {$column} {$operator} {$param})";
        }
        return $this;
    }
    public function scalar(): mixed
    {
        $sql = $this->toSql();
        $stmt = Application::$app->db->pdo->prepare($sql);
        foreach ($this->bindings as $param => $value) {
            $this->bindValueWithType($stmt, $param, $value);
        }
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_NUM);
        return $result[0] ?? null;
    }
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy = $this->prefixColumn($column) . ' ' . strtoupper($direction);
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function groupBy(string $column): self
    {
        $this->groupBy = $this->prefixColumn($column);
        return $this;
    }

    public function having(string $condition): self
    {
        $this->having = $condition;
        return $this;
    }

    public function count(string $column = '*'): int
    {
        $col = $column === '*' ? '*' : $this->prefixColumn($column);
        $sql = "SELECT COUNT({$col}) AS total FROM {$this->table}";
        if ($this->alias) {
            $sql .= " AS {$this->alias}";
        }
        $sql .= $this->buildJoins();
        $sql .= $this->buildWhere();

        $stmt = Application::$app->db->pdo->prepare($sql);
        foreach ($this->bindings as $param => $value) {
            $this->bindValueWithType($stmt, $param, $value);
        }
        $stmt->execute();
        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function all(): array
    {
        $sql = $this->toSql();
        $stmt = Application::$app->db->pdo->prepare($sql);
        foreach ($this->bindings as $param => $value) {
            $this->bindValueWithType($stmt, $param, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_CLASS, $this->modelClass);
    }

    public function first(): mixed
    {
        $this->limit(1);
        $results = $this->all();
        return $results[0] ?? null;
    }
    public function joinRaw(string $type, string $tableWithAlias, string $onClause): self
    {
        $this->joins[] = strtoupper($type) . " JOIN {$tableWithAlias} ON {$onClause}";
        return $this;
    }
    public function distinct(bool $flag = true): static
    {
        $this->isDistinct = $flag;
        return $this;
    }
    public function toSql(): string
    {
        
        $sql = 'SELECT ' . ($this->select ? implode(', ', $this->select) : '*');
        $sql .= " FROM {$this->table}";
        if ($this->alias) {
            $sql .= " AS {$this->alias}";
        }
        $sql .= $this->buildJoins();
        $sql .= $this->buildWhere();

        if ($this->groupBy) {
            $sql .= " GROUP BY {$this->groupBy}";
        }

        if ($this->having) {
            $sql .= " HAVING {$this->having}";
        }

        if ($this->orderBy) {
            $sql .= " ORDER BY {$this->orderBy}";
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    protected function buildJoins(): string
    {
        return $this->joins ? ' ' . implode(' ', $this->joins) : '';
    }

    protected function buildWhere(): string
    {
        return $this->where ? ' WHERE ' . implode(' AND ', $this->where) : '';
    }
    protected array $whereConditions = [];

    public function startGroup(): self
    {
        $this->whereConditions[] = '(';
        return $this;
    }

    public function endGroup(): self
    {
        $this->whereConditions[] = ')';
        return $this;
    }
    protected function bindValueWithType($stmt, string $param, $value): void
    {
        if (is_bool($value)) {
            $stmt->bindValue($param, $value, PDO::PARAM_BOOL);
        } elseif (is_int($value)) {
            $stmt->bindValue($param, $value, PDO::PARAM_INT);
        } elseif (is_null($value)) {
            $stmt->bindValue($param, $value, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue($param, $value, PDO::PARAM_STR);
        }
    }

    public function andWhere(array $condition): self
    {
        [$column, $operator, $value] = $condition;
        if (strtoupper($operator) === 'IS' && strtoupper($value) === 'NULL') {
            $this->where[] = "{$column} IS NULL";
        } else {
            $param = ':param' . count($this->bindings);
            $this->bindings[$param] = $value;
            $this->where[] = "{$column} {$operator} {$param}";
        }
        return $this;
    }

    public function orWhereGroup(array $conditions): static
    {
        $group = [];
        foreach ($conditions as $cond) {
            foreach ($cond as $col => $val) {
                $group[] = ['OR', $col, 'LIKE', $val];
            }
        }
        $this->where[] = ['GROUP', $group];
        return $this;
    }
    public function cloneWithoutLimitOffset(): self
    {
        $new = clone $this;
        $new->limit = null;
        $new->offset = null;
        return $new;
    }

    public function whereGroup(callable $callback): self
    {
        $this->startGroup();          // Push "("
        $callback($this);            // Let the user add orWhere() or where()s
        $this->endGroup();           // Push ")"
        return $this;
    }

    protected function prefixColumn(string $column): string
    {
        if (strpos($column, '.') !== false || strpos($column, '(') !== false) {
            return $column;
        }
        return $this->alias ? "{$this->alias}.$column" : "{$this->table}.$column";
    }
}
