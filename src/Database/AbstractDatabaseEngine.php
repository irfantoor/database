<?php
/**
 * IrfanTOOR\Database\AbstractDatabaseEngine
 * php version 7.3
 *
 * @package   IrfanTOOR\Database
 * @author    Irfan TOOR <email@irfantoor.com>
 * @copyright 2020 Irfan TOOR
 */

namespace IrfanTOOR\Database;

use Exception;
use PDOException;

abstract class AbstractDatabaseEngine
{
    /**
     * @var DatabaseEngineInterface
     */
    protected $db;

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
    public function __construct(array $connection)
    {
        $this->connect($connection);
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
    public function insert(string $table, array $record, array $bind = [])
    {
        extract(self::$defaults, EXTR_SKIP);

        $sql = 'INSERT INTO ' . $table . ' ' .
               '(' . implode(', ', array_keys($record)) . ') VALUES (';

        $sep = '';

        foreach ($record as $k => $v) {
            if (isset($bind[$k])) {
                $sql .= $sep . ':__' . $k;
                $bind['__' . $k] = $v;
            } else {
                $sql .= $sep . ':' . $k;
            }
            
            $sep = ', ';
        }

        $sql .= ');';

        foreach ($record as $k => $v) {
            if (!isset($bind[$k])) {
                $bind[$k] = $v;
            }
        }

        return $this->query($sql, $bind);
    }

    /**
     * @inheritdoc
     */
    public function update(string $table, array $record, array $options = [])
    {
        extract(self::$defaults, EXTR_SKIP);
        extract($options);

        $sql = 'UPDATE ' . $table . ' SET ';
        $sep = '';

        foreach ($record as $k => $v) {
            if (isset($bind[$k])) {
                $sql .= $sep . "$k = :__$k";
                $bind['__' . $k] = $v;
            } else {
                $sql .= $sep . "$k = :$k";
            }
            
            $sep = ', ';
        }
        
        $sql .= ' WHERE ' . $where; // ' LIMIT ' . $limit;

        foreach ($record as $k => $v) {
            if (!isset($bind[$k])) {
                $bind[$k] = $v;
            }
        }

        return $this->query($sql, $bind);
    }

    /**
     * @inheritdoc
     */
    public function remove(string $table, array $options)
    {
        extract(self::$defaults, EXTR_SKIP);
        unset($where);
        extract($options);

        if (!$where) {
            throw new Exception("where condition is required", 1);
        }

        $sql =  'DELETE FROM ' . $table;
        $sql .= ' WHERE ' . $where; // ' LIMIT '    . $limit;

        return $this->query($sql, $bind);
    }

    /**
     * @inheritdoc
     */
    public function get(string $table, array $options = [])
    {
        extract(self::$defaults, EXTR_SKIP);
        $limit = [0, 100];
        extract($options);

        if (is_array($limit)) {
            $offset = $limit[0];
            $limit = $limit[1];
        }

        $sql = 'SELECT ' . $select .
            ' FROM '  . $table .
            ' WHERE ' . $where .
            ($order_by !== '' ? ' ORDER BY ' . $order_by : '') .
            ' LIMIT ' . $offset . ',' . $limit;

        return $this->query($sql, $bind);
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
