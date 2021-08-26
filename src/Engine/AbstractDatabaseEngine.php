<?php

/**
 * IrfanTOOR\Database\Engine\AbstractDatabaseEngine
 * php version 7.3
 *
 * @author    Irfan TOOR <email@irfantoor.com>
 * @copyright 2021 Irfan TOOR
 */

namespace IrfanTOOR\Database\Engine;

use Exception;
use IrfanTOOR\Database\Query;
use PDOException;

abstract class AbstractDatabaseEngine
{
    /**
     * @var DatabaseEngineInterface
     */
    protected $db;

    /**
     * @var Query
     */
    protected $query;

    /**
     * @var array
     */
    protected static $defaults = [
        'table'    => '',
        'select'   => '*',
        'where'    => '1 = 1',
        'order_by' => '',
        'limit'    => 1,
        'offset'   => 0,
        'bind'     => [],
    ];

    /**
     * Construct Engine
     *
     * @param array $connection Associative array giving connection parameters
     */
    public function __construct(?array $connection = null)
    {
        if ($connection)
            $this->connect($connection);

        $this->query = new Query();
    }

    /**
     * @inheritdoc
     */
    public function query(string $sql, array $bind = [])
    {
        try {
            $q = $this->db->prepare($sql);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }

        foreach ($bind as $k => $v) {
            $$k = $v;
            // todo -- bindParam( ... , PDO::PARAM_INT ...);
            $q->bindParam(':' . $k, $$k);
        }

        $result = $q->execute();

        if (strpos(trim(strtoupper($sql)), 'SELECT') === 0) {
            $rows = [];

            while ($row = $q->fetch()) {
                $rows[] = $row;
            }

            return $rows;
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function execute(?Query $query = null)
    {
        if ($query)
            $this->query = $query;

        $sql  = $this->query->__toString();
        $bind = $this->query->get('bind');

        return $this->query($sql, $bind);
    }

    /**
     * @inheritdoc
     */
    public function insert(string $table, array $record)
    {
        $this->query
            ->init()
            ->insert($record)
            ->into($table)
        ;

        return $this->execute();
    }

    /**
     * @inheritdoc
     */
    public function update(string $table, array $record, array $options = [])
    {
        $this->query
            ->init()
            ->update($record)
            ->into($table)
            ->options($options)
        ;

        return $this->execute();
    }

    /**
     * @inheritdoc
     */
    public function remove(string $table, array $options)
    {
        $this->query
            ->init()
            ->delete()
            ->from($table)
            ->options($options)
        ;

        return $this->execute();
    }

    /**
     * @inheritdoc
     */
    public function get(string $table, array $options = [])
    {
        $this->query
            ->init()
            ->select('*')
            ->from($table)
            ->options($options)
        ;

        return $this->execute();
    }

    /**
     * @inheritdoc
     */
    public function getFirst(string $table, array $options = [])
    {
        $options['limit'] = 1;
        $list = $this->get($table, $options);

        return (count($list)) ? $list[0] : null;
    }

    /**
     * @inheritdoc
     */
    public function has(string $table, array $options = []): bool
    {
        $r = $this->getFirst($table, $options);
        return $r ? true : false;
    }
}
