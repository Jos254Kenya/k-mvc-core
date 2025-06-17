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
        $sql = "SELECT COUNT({$col}) AS total FROM `{$this->table}`";
        if ($this->alias) {
            $sql .= " AS `{$this->alias}`";
        }
        $sql .= $this->buildJoins();
        $sql .= $this->buildWhere();

        $stmt = Application::$app->db->pdo->prepare($sql);
        $stmt->execute($this->bindings);
        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function all(): array
    {
        $sql = $this->toSql();
        $stmt = Application::$app->db->pdo->prepare($sql);
        $stmt->execute($this->bindings);
        return $stmt->fetchAll(PDO::FETCH_CLASS, $this->modelClass);
    }

    public function first(): mixed
    {
        $this->limit(1);
        $results = $this->all();
        return $results[0] ?? null;
    }

    public function toSql(): string
    {
        $sql = 'SELECT ' . ($this->select ? implode(', ', $this->select) : '*');
        $sql .= " FROM `{$this->table}`";
        if ($this->alias) {
            $sql .= " AS `{$this->alias}`";
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
    protected function prefixColumn(string $column): string
    {
        if (strpos($column, '.') !== false || strpos($column, '(') !== false) {
            return $column;
        }
        return $this->alias ? "`{$this->alias}`.`$column`" : "`{$this->table}`.`$column`";
    }
}
