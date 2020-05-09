<?php
/**
 * IrfanTOOR\Database\DatabaseEngineInterface
 * php version 7.3
 *
 * @package   IrfanTOOR\Database
 * @author    Irfan TOOR <email@irfantoor.com>
 * @copyright 2020 Irfan TOOR
 */
namespace IrfanTOOR\Database;

/**
 * Defines the DatabaseEngineInterface
 */
Interface DatabaseEngineInterface
{
    /**
     * Connect to a database
     * 
     * $param  array $connection
     * @return bool
     */
    public function connect(array $connection): bool;

    /**
     * Executes a raw SQL
     * 
     * @param string $sql,
     * @param array  $bind associative array to bind data in sql while preparing
     *                     see DatabaseEngineInterface::update
     */
    public function query(string $sql, array $data = []);

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
    public function insert(string $table, array $record, array $bind = []);

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
     *                            'where' => '1 = 1',
     *                            'limit' => 1, // see DatabaseEngineInterface::get
     *                            'bind'  => [],
     * 
     * @return bool result of the update operation
     */
    public function update(string $table, array $record, array $options = []);

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
    public function remove(string $table, array $options);

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
     * @return array [rows]   containing the array of rows or null if not found
     */
    public function get(string $table, array $options = []);

    /**
     * Retreives only the first record
     * 
     * @param string $table   name of the table e.g. $table = 'useres';
     * @param array  $options as explained in DatabaseEngineInterface::get
     * 
     * @return array  containing the associative key=>value pairs of the row or null otherwise
     */
    public function getFirst(string $table, array $options = []);
}
