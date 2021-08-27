<?php

namespace IrfanTOOR\Database;

use Exception;

/**
 * Query class to create and manipulate a query functionally, without
 * worring to keep the right order
 *
 * e.g. $q = (new Query)->from('users');
 * echo $q will print:
 * SELECT * FROM users limit 0, 10;
 *
 * OR   $q = (new Query)
 *      ->from('users')
 *      ->where('name like :name')
 *      ->where('dob < :dob')
 *      ->orderby('name)
 *      ->limt(10)
 *      ->bind('name' => 'a%', 'dob' => '2002-01-01')
 *      ; # note order of the bove functions is not important
 * echo $q will print:
 * SELECT * FROM users WHERE name like :name AND dob < :dob ORDER BY name limit 10;
 *
 * $q = (new Query())->delete()->from('users')->where('id=1');
 *
 * an existsing instance of query can be initialized to reuse
 * e.g $q = $q->init()->from('contacts')->limit('100');
 *
 */
class Query
{
    /** Default limit for a SELECT query */
    const DEFAULT_LIMIT  = '0, 10';

    /** Default limit for a DELETE query */
    const DEFAULT_DELETE_LIMIT = '1';

    /** @var array Components of the Query */
    protected $sql;

    /**
     * Query constructor
     *
     * @param array $init Optional associative array ot initialize the query
     */
    public function __construct(array $init = [])
    {
        $this->init($init);
    }

    /** Used for the aliases of the method 'from' */
    public function __call($method, $args)
    {
        switch ($method) {
            case 'table':
            case 'into' :
            case 'in':
                return call_user_func_array([$this, 'from'], $args);
                break;

            default:
                throw new Exception("Unknow method: $method");
        }

        return $this;
    }

    /** Initializes the query */
    public function init(array $defaults = [])
    {
        $this->sql = array_merge(
            [
                'raw'      => null,
                'action'   => 'select', # can be select, insert, update, delete

                'select'   => '*',
                'table'     => 'TABLE_NAME',
                'joins'    => [],
                'where'    => null,
                'orderby'  => null,
                'limit'    => null,

                'record'   => [],
                'bind'     => [],
            ],
            $defaults
        );

        return $this;
    }

    /**
     * Retrieves the value of a query component
     *
     * @param string $component
     */
    public function get(string $component)
    {
        if (in_array($component , ['from', 'table', 'into', 'in']))
            return $this->sql['table'] ?? null;

        return $this->sql[$component] ?? null;
    }

    /**
     * Passes the options array to query by calling related function
     *
     * @param array $data Associative array of function to parameter
     * @return self
     */
    public function options(array $data): self
    {
        foreach ($data as $k => $v) {
            try {
                $this->$k($v);
            } catch(\Throwable $th)
            {}
        }

        return $this;
    }

    /**
     * Sets the raw query string of this query
     *
     * @param string $raw
     * @return self
     */
    public function raw(string $raw): self
    {
        $this->sql['raw'] = $raw;
        return $this;
    }

    /**
     * Sets the SELECT fields
     *
     * @param string $fields Fields to be queried, default is '*' for all
     * @return self
     */
    public function select(string $fields = '*'): self
    {
        $this->sql['select'] = $fields;
        return $this;
    }

    /**
     * Set the FROM field
     *
     * @param string $tbl_name Name of the table to query
     * @return self
     */
    public function from(string $tbl_name): self
    {
        $this->sql['table'] = $tbl_name;
        return $this;
    }

    /**
     * Joins
     *
     * @param string Join statement, multiple join statements can be added by calling
     *               the function multiple times.
     * @return self
     */
    public function join(string $join): self
    {
        $this->sql['joins'][] = $join;
        return $this;
    }

    /**
     * Where conditions of the query
     * e.g. ... ->where('id<10')->where('name like '%abc%') ...
     *
     * @param string $condition
     * @return self
     */
    public function where(string $condition, $operator = 'AND'): self
    {
        $this->sql['where'] =
            (
                $this->sql['where']
                ? "(" . $this->sql['where'] . " $operator " . $condition . ")"
                : $condition
            )
        ;
        return $this;
    }

    /**
     * Orderby part of the query
     * e.g. ... ->orderby('DATE DESC')->orderby('name ASC') ...
     *
     * @param string $order define the sorting order
     * @return self
     */
    public function orderby(string $order): self
    {
        $this->sql['orderby'] =
            (
                $this->sql['orderby']
                ? ($this->sql['orderby']) . ", "
                : ""
            ) .
            $order
        ;
        return $this;
    }

    /**
     * Sets the limit of the query
     *
     * @param string $limit Limit of the query e.g. '0,100' or '10'
     * @return self
     */
    public function limit(string $limit): self
    {
        $this->sql['limit'] = $limit;
        return $this;
    }

    /**
     * Record to be added, for insert or update queries
     *
     * @param array $record Record
     * @return self
     */
    public function record(array $record): self
    {
        $this->sql['record'] = $record;
        return $this;
    }

    /**
     * Data to be bound to query while preparation
     *
     * @param array $data
     * @return self
     */
    public function bind(array $data): self
    {
        foreach ($data as $k => $v)
            $this->sql['bind'][$k] = $v;

        return $this;
    }

    /**
     * Sets the record and marks the query as an INSERT query
     *
     * @param array $record Record to be inserted
     * @return self
     */
    public function insert(array $record): self
    {
        $this->sql['action'] = 'insert';
        return $this->record($record);
    }

    /**
     * Sets the record and marks the query as UPDATE query
     *
     * @param array $record Record to be updated
     * @return self
     */
    public function update(array $record): self
    {
        $this->sql['action'] = 'update';
        return $this->record($record);
    }

    /**
     * Sets the record and marks the query as an INSERT OR REPLACE query
     *
     * @param array $record Record to be inserted
     * @return self
     */
    public function insertOrUpdate(array $record)
    {
        $this->sql['action'] = 'replace';
        return $this->record($record);
    }

    /**
     * Marks the query as a DELETE query
     * e.g. ->delete('users')->where(...)
     * OR   ->delete()->from('users')->where(...)
     *
     * @param string|null $tbl_name Tablename for the delete operation (optional)
     *                              can be defined through a successive 'from' call
     */
    public function delete(?string $tbl_name = null): self
    {
        $this->sql['action'] = 'delete';
        if ($tbl_name)
            return $this->from($tbl_name);

        return $this;
    }

    /**
     * Prepares a SELECT query
     */
    protected function selectQuery(): string
    {
        $joins = '';
        foreach ($this->sql['joins'] as $j) {
            $joins .= $j;
        }

        return
            'SELECT ' . $this->sql['select'] .
            ' FROM '  . $this->sql['table'] .
            ($this->sql['where'] ? ' WHERE ' . $this->sql['where'] : '') .
            $joins .
            ($this->sql['orderby'] ? ' ORDER BY ' . $this->sql['orderby'] : '') .
            ( strpos($this->sql['select'], '(') === false
                ? (' LIMIT ' . ($this->sql['limit'] ?? self::DEFAULT_LIMIT))
                : ''
            ) .
            ';'
        ;
    }

    /**
     * Prepares the string and bind values for UPDATE query
     */
    protected function prepareValues(): string
    {
        $values = $sep = '';

        foreach ($this->sql['record'] as $k => $v) {
            $kk = 'record_' . $k;
            $values .= $sep . ":" . $kk;
            $this->sql['bind'][$kk] = $v;
            $sep = ', ';
        }

        return $values;
    }

    /**
     * Prepare the string and bind values for INSERT query
     */
    protected function prepareIndividualValues(): string
    {
        $values = $sep = '';

        foreach ($this->sql['record'] as $k => $v) {
            $kk = 'record_' . $k;
            $values .= $sep . "$k=:$kk";
            $this->sql['bind'][$kk] = $v;
            $sep = ', ';
        }

        return $values;
    }

    /**
     * Prepares an INSERT query
     */
    protected function insertQuery(bool $replace = false): string
    {
        return
            ($replace ? 'INSERT OR REPLACE' : 'INSERT') .
            ' INTO ' . $this->sql['table'] .
            ' ( ' .
                implode(', ', array_keys($this->sql['record'])) .
            ' ) ' .
            ' VALUES (' .
                $this->prepareValues() .
            ' );'
        ;
    }

    /**
     * Prepares an UPDATE query
     */
    protected function updateQuery(): string
    {
        return
            'UPDATE ' . $this->sql['table'] .
            ' SET ' .
                $this->prepareIndividualValues() .
            ' WHERE ' . $this->sql['where']
        ;
    }

    /**
     * Prepares a DELETE query
     */
    protected function deleteQuery(): string
    {
        return
            'DELETE' .
            ' FROM '  . $this->sql['table'] .
            ' WHERE ' . ($this->sql['where'] ?? '0=1') .
            // ' LIMIT ' . ($this->sql['limit'] ?? self::DEFAULT_DELETE_LIMIT) .
            ';'
        ;
    }

    /**
     * Converts the Query into a SQL statement
     *
     * @return string
     */
    public function __toString(): string
    {
        if ($this->sql['raw'])
            return $this->sql['raw'];

        switch ($this->sql['action']) {
            case 'select':
                return $this->selectQuery();

            case 'insert':
                return $this->insertQuery();

            case 'replace':
                return $this->insertQuery(true);

            case 'update':
                return $this->updateQuery();

            case 'delete':
                return $this->deleteQuery();
        }

        return "";
    }
}
