<?php

/**
 * IrfanTOOR\Database\Model
 * php version 7.3
 *
 * @author    Irfan TOOR <email@irfantoor.com>
 * @copyright 2021 Irfan TOOR
 */

namespace IrfanTOOR\Database;

use Exception;
use IrfanTOOR\Database\Engine\SQLite;
use Throwable;

/**
 *   !!! NOTE: Currently Model only supports SQLite db
 *
 *   e.g.
 *   class Users extends IrfanTOOR\Database\Model
 *   {
 *       function __construct($connection)
 *       {
 *           $this->schema = [
 *               'id' => 'INTEGER PRIMARY KEY',
 *
 *               'name'       => 'NOT NULL',
 *               'email'      => 'COLLATE NOCASE',
 *               'password'   => 'NOT NULL',
 *               'token',
 *               'validated'  => 'BOOL DEFAULT false',
 *
 *               'created_on' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
 *               'updated_on' => 'INTEGER'
 *           ];
 *
 *           $this->indices = [
 *               ['index'  => 'name'],
 *               ['unique' => 'email'],
 *           ];
 *
 *           parent::__construct($connection);
 *       }
 *   }
 *
 *   $users = new Users(['file' => 'users.sqlite', 'table' => 'users']);
 *   $user->addOrUpdate(
 *       [
 *           'name' => 'Someone',
 *           'email' => 'someone@eample.com',
 *           'password' => 'Hello World!',
 *       ]
 *   );
 *
 *   $user = $users->getFirst([
 *          'where' => 'id = :id',
 *          'bind' => ['id' => $_GET['id']],
 *   ]);
 *
 *   print_r($user['email']);
 */
class Model
{
    /**
     * @var array
     */
    protected $schema = [];

    /**
     * @var array
     */
    protected $indices = [];

    /**
     * @var SQlite
     */
    protected $db = null;

    /**
     * Sqlite database file
     *
     * @var string
     */
    protected $file = '';

    /**
     * Name of the table, the model is based upon
     *
     * @var string
     */
    protected $table = '';

    /**
     * Model constructor
     *
     * @param array $connection - Associative array of connection parameters
     */
    function __construct(array $connection = [])
    {
        if (!array_key_exists('file', $connection))
            throw new Exception("file is missing in the connection");

        $this->file = $connection['file'];

        $deploy_schema = false;
        $create = $connection['create'] ?? false;

        if (!is_string($this->file)) {
            throw new Exception("file must be a string");
        } elseif (!file_exists($this->file)) {
            if ($create) {
                file_put_contents($this->file, '');
                $deploy_schema = true;
            } else {
                throw new Exception("file: {$this->file}, does not exist");
            }
        }

        $table = $connection['table'] ?? null;

        // convert modelname to tablename e.g. Users::class will create the
        // table 'users'
        if (!$table) {
            $class = explode('\\', get_called_class());
            $table = strtolower(array_pop($class));
        }

        $this->table = $table;

        try {
            $this->db = new SQLite(['file' => $this->file]);
        } catch (Throwable $e) {
            throw new Exception($e->getMessage());
        }

        // deploy the schema if its a newly created file
        if ($deploy_schema)
            $this->deploySchema();
    }

    /**
     * Adds a field to the schema
     *
     * @param string $fld
     * @param string $def
     */
    function addField(string $fld, string $def = '')
    {
        $this->schema[$fld] = $def;
    }

    /**
     * Adds a field to the schema
     *
     * @param string $fld
     * @param string $def
     *
     * @return bool True if successful, false if not present or on failure
     */
    function removeField(string $fld): bool
    {
        if (!array_key_exists($fld, $this->schema))
            return false;

        unset($this->schema[$fld]);
        $this->removeIndex($fld);
        return true;
    }

    /**
     * Adds an index
     *
     * @param string $fld
     * @param bool   $unique
     */
    function addIndex(string $fld, bool $unique = false)
    {
        $indices = [];

        foreach ($this->indices as $index) {
            foreach ($index as $type => $field) {
                if ($fld === $field)
                    continue;
                $indices[] = [$type => $field];
            }
        }

        $indices[] = [($unique ? "unique" : "index") => $fld];
        $this->indices = $indices;
    }

    /**
     * Removes an index
     *
     * @param string $fld
     * @param bool   $unique
     */
    function removeIndex(string $fld)
    {
        $indices = [];

        foreach ($this->indices as $index) {
            foreach ($index as $type => $field) {
                if ($fld === $field)
                    continue;
                $indices[] = [$type => $field];
            }
        }

        $this->indices = $indices;
    }

    /**
     * Retrieves the name of the database file
     *
     * @return string
     */
    function getDatabaseFile(): string
    {
        return $this->file;
    }

    /**
     * Prepares the schema
     *
     * @return string Raw SQL schema, prepared from the definition of schema and
     *                indices, which were provided while wrinting the model
     *                (ref: Creating a Model), is returned. This schema can be used
     *                to create the sqlite file manually.
     */
    function prepareSchema(): string
    {
        // Database structure
        $schema = 'CREATE TABLE IF NOT EXISTS ' . $this->table . ' (';
        $sep    = PHP_EOL;

        foreach ($this->schema as $fld => $def) {
            if (is_int($fld)) {
                $fld = $def;
                $def = '';
            }

            $schema .= $sep . $fld . ($def ? ' ' . $def : '');
            $sep = ', ' . PHP_EOL;
        }

        $schema .= PHP_EOL . ');' . PHP_EOL;

        // Add indices
        foreach ($this->indices as $index) {
            foreach ($index as $type => $field) {
                $type = strtolower($type);

                switch ($type) {
                case 'u':
                case 'unique':
                    $schema .=  'CREATE UNIQUE INDEX ';
                    break;
                case 'i':
                case 'index':
                default:
                    $schema .=  'CREATE INDEX ';
                }

                $schema .=
                    "{$this->table}_{$field}_{$type}" .
                    ' ON ' . $this->table . '(' . $field . ');' . PHP_EOL;
            }
        }

        return $schema;
    }

    /**
     * Deploy the schema
     *
     * @param string $schema - The schema to be deployed to the connected file
     *
     * @throws Exception in case of error
     *
     * @return void
     */
    public function deploySchema(?string $schema = null)
    {
        if (!$schema)
            $schema = $this->prepareSchema();

        try {
            $schema = explode(';', str_replace(PHP_EOL, '', $schema));

            // removes the empty element at end
            array_pop($schema);

            // create the db structure
            $sql = array_shift($schema);
            $this->db->query($sql . ';');

            // create the indices now
            foreach ($schema as $sql) {
                $this->db->query($sql . ';');
            }
        } catch(Throwable $e) {
            throw new Exception($e->getMessage(), 1);
        }
    }

    /**
     * Insert a record
     *
     * @param array $record Associative array represnting one record
     * @param array $bind   The data to bind to the :placeholders in $record
     *
     * @return bool Result of the operation
     */
    public function insert(array $record): bool
    {
        return $this->db->insert($this->table, $record);
    }

    /**
     * Insert or update a record
     *
     * @param array $record Associative array represnting one record
     * @param array $bind   The data to bind to the :placeholders in $record
     *
     * @return bool Result of the operation
     */
    public function insertOrUpdate(array $record): bool
    {
        return $this->db->insertOrUpdate($this->table, $record);
    }

    /**
     * Update an existing record
     *
     * @param array $record  Associative array represnting one record
     * @param array $options The where clause or the binding data etc.
     *
     * @return bool Result of the operation
     */
    public function update(array $record, array $options = []): bool
    {
        return $this->db->update($this->table, $record, $options);
    }

    /**
     * Remove an existing record
     *
     * @param array $options The where clause or the binding data etc.
     *
     * @return bool Result of the operation
     */
    public function remove(array $options): bool
    {
        return $this->db->remove($this->table, $options);
    }

    /**
     * Retrieve a list of records
     *
     * @param array $options The where clause, or the binding data etc.
     *                       this might include the order_by and limit parameters
     *
     * @return mixed Array of rows or null if no records found
     */
    public function get(array $options = [])
    {
        return $this->db->get($this->table, $options);
    }

    /**
     * Retrieve the first record
     *
     * @param array $options The where clause or the binding data etc.
     *                       this might include the order_by and limit parameters
     *
     * @return mixed Aassociative array or record or null if not found
     */
    public function getFirst(array $options = [])
    {
        return $this->db->getFirst($this->table, $options);
    }

    /**
     * Verify if a record exists
     *
     * @param array $options - The where clause or the binding data etc.
     *
     * @return bool True if a record exists, false otherwise
     */
    public function has($options = []): bool
    {
        return $this->db->has($this->table, $options);
    }
}
