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
use PDO;
use PDOException;

abstract class AbstractDatabaseEngine
{
    /**
     * @var DatabaseEngine
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
     * Database Engine Constructor
     * 
     * $param  array $connection
     */
    public function __construct(array $connection)
    {
        $this->connect($connection);
    }    

    /**
     * Executes a raw SQL
     * 
     * @param string $sql,
     * @param array  $bind associative array to bind data in sql while preparing
     *                     see DatabaseEngineInterface::update
     *
     * @return bool|array
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
            # todo -- bindParam( ... , PDO::PARAM_INT ...);
            $q->bindParam(':' . $k, $$k); 
        }

        $result = $q->execute();

        if (strpos(trim(strtoupper($sql)), 'SELECT') === 0) {
            $rows = [];
            while($row = $q->fetch()) {
                $rows[] = $row;
            }
            return $rows;
        }

        return $result;
    }

    /**
     * Inserts a record into a connected database
     * 
     * @param string $table
     * @param array  $record  associative array of record, values might contain
     *                        variables of the form :id etc, which are filled using
     *                        the prepare mechanism, taking data from bind array
     *                        e.g. ['id' => :id, 'name' => :name ]
     *                        Note: record must contain all of the required fields
     * @param array  $bind    associative array e.g. ['id' => $_GET['id'] ?? 1], 
     *                        see DatabaseEngineInterface::update for bind details
     * 
     * @return bool result of the insert operation
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
     * Updates an existing record
     * 
     * @param string $table
     * @param array  $record  associated array only includes data to be updated
     *                        e.g $record = [
     *                                'id'       => 1,
     *                                'user'     => 'root', 
     *                                'password' => 'toor',
     *                                'groups'   => 'admin,user,backup',
     *                                'remote'   => false,
     *                            ];
     * @param array  $options contains where, limit or bind etc.
     *                        e.g $options = [
     *                                'where' => 'id = :id', <------------+
     *                                'limit' => 1,                       |
     *                                'bind' => [                         |
     *                                    'id' => $_GET['root_id'] ?? 1, -+
     *                                ]
     *                            ];
     *                         If options are not provided following are the assumed defaults:
     *                         [
     *                            'where' => '1 = 1',
     *                            'limit' => 1, // see DatabaseEngineInterface::get
     *                            'bind'  => [],
     *                         ]
     * 
     * @return bool result of the update operation
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
        
        $sql .= ' WHERE ' . $where; # ' LIMIT ' . $limit;

        foreach ($record as $k => $v) {
            if (!isset($bind[$k])) {
                $bind[$k] = $v;
            }
        }

        return $this->query($sql, $bind);
    }

    /**
     * Removes a record from database
     * 
     * @param string $table
     * @param array  $options contains where, limit or bind options
     *                        see DatabaseEngineInterface::update for details
     *                        If options are not provided following are the assumed defaults:
     *                            'where' => '1 = 0', # forces that a where be provided
     *                            'limit' => 1,       # see DatabaseEngineInterface::get
     *                            'bind'  => [],      # see DatabaseEngineInterface::update

     * 
     * @return bool result of the update operation
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
        $sql .= ' WHERE ' . $where; # ' LIMIT '    . $limit;

        return $this->query($sql, $bind);
    }

    /**
     * Retreives list of records
     * 
     * @param string $table
     * @param array  $options associated array containing where, order_by, limit and bind
     *                        if limit is an int, the records are retrived from start, if its
     *                        an array it is interpretted like [int $from, int $count], $from
     *                        indicates number of records to skip and $count indicates number
     *                        of records to retrieve.
     *                        e.g. $options = [
     *                                 'limit' => 1 or 'limit' => [0, 10]
     *                                 'order_by' => 'ASC id, DESC date',
     *                                 'where' => 'date < :date', <---------------------------+
     *                                 'bind' => ['date' => $_POST['date'] ?? date('d-m-Y')], +
     *                                 # bind: see DatabaseEngineInterface::update
     *                             ];
     * 
     * @return array [rows]   containing the array of rows or empty array otherwise
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

        $sql =
            'SELECT ' . $select .
            ' FROM '  . $table .
            ' WHERE ' . $where .
            ($order_by !== '' ? ' ORDER BY ' . $order_by : '') .
            ' LIMIT ' . $offset . ',' . $limit;

        return $this->query($sql, $bind);
    }

    /**
     * Retreives only the first record
     * 
     * @param string $table   name of the table e.g. $table = 'useres';
     * @param array  $options as explained in DatabaseEngineInterface::get
     * 
     * @return array  containing the associative key=>value pairs of the row or null otherwise
     */
    public function getFirst(string $table, array $options = [])
    {
        $options['limit'] = 1;
        $list = $this->get($table, $options);

        return (count($list)) ? $list[0] : null;
    }

    /**
     * Verifies that the database has the record(s)
     *
     * @param string $table
     * @param array $options see DatabaseEngineInterface::update
     */
    public function has(string $table, array $options = [])
    {
        $r = $this->getFirst($table, $options);
        return $r ? true : false;
    }    
}
