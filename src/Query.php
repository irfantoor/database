<?php

namespace IrfanTOOR\Database;

use Exception;

class Query
{
    const DEFAULT_LIMIT  = '0, 10';
    const DEFAULT_DELETE_LIMIT = '1';

    protected $sql;

    public function __construct()
    {
        $this->init();
    }

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

    public function get($k)
    {
        return $this->sql[$k] ?? null;
    }

    public function options(array $data)
    {
        foreach ($data as $k => $v) {
            $this->$k($v);
        }
    }

    public function raw(string $raw)
    {
        $this->sql['raw'] = $raw;
        return $this;
    }

    public function select(string $fields = '*'): self
    {
        $this->sql['select'] = $fields;
        return $this;
    }

    public function from(string $tbl_name): self
    {
        $this->sql['table'] = $tbl_name;
        return $this;
    }

    public function join(string $join)
    {
        $this->sql['joins'][] = $join;
        return $this;
    }

    public function where(string $condition): self
    {
        $this->sql['where'] = $condition;
        return $this;
    }

    public function orderby(string $order): self
    {
        $this->sql['orderby'] = $order;
        return $this;
    }

    public function limit(string $limit): self
    {
        $this->sql['limit'] = $limit;
        return $this;
    }

    public function record(array $record): self
    {
        $this->sql['record'] = $record;
        return $this;
    }

    public function bind(array $data): self
    {
        foreach ($data as $k => $v)
            $this->sql['bind'][$k] = $v;

        return $this;
    }

    public function insert(array $record): self
    {
        $this->sql['action'] = 'insert';
        $this->sql['record'] = $record;

        return $this;
    }

    public function update(array $record): self
    {
        $this->sql['action'] = 'update';
        $this->sql['record'] = $record;

        return $this;
    }

    public function insertOrUpdate(array $record)
    {
        $this->sql['action'] = 'replace';
        $this->sql['record'] = $record;

        return $this;
    }

    public function delete(?string $tbl_name = null): self
    {
        $this->sql['action'] = 'delete';
        if ($tbl_name)
            return $this->from($tbl_name);

        return $this;
    }

    protected function selectQuery()
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

    protected function prepareValues()
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

    protected function prepareIndividualValues()
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

    protected function insertQuery(bool $replace = false)
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

    protected function updateQuery()
    {
        return
            'UPDATE ' . $this->sql['table'] .
            ' SET ' .
                $this->prepareIndividualValues() .
                # id = :record_id,
                // implode(', ', array_keys($this->sql['record'])) .
            ' WHERE ' . $this->sql['where']
        ;
    }

    protected function deleteQuery()
    {
        if (!$this->sql['where'])
            throw new Exception("WHERE condition is necessary for DELETE Query");

        return
            'DELETE' .
            ' FROM '  . $this->sql['table'] .
            ' WHERE ' . $this->sql['where'] .
            // ' LIMIT ' . ($this->sql['limit'] ?? self::DEFAULT_DELETE_LIMIT) .
            ';'
        ;
    }

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
