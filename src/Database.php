<?php

/**
 * IrfanTOOR\Database
 * php version 7.3
 *
 * @author    Irfan TOOR <email@irfantoor.com>
 * @copyright 2021 Irfan TOOR
 */

namespace IrfanTOOR;

use Exception;
use IrfanTOOR\Database\Engine\DatabaseEngineInterface;

/**
 * Irfan's Database, A bare-minimum and simple database access.
 *
 * You can connect the Database engines like MySAL, SQLite, MSSQL, Couchbase ...
 *
 * e.g.
 *   $db = new Database('sqlite', ['file' => 'posts.sqlite']);
 *   # or
 *   $db = (new Database('sqlite'))->connect(['file' => 'posts.sqlite']);
 *   $result = $db->query('SELECT count(*) from users');
 *   $db->insert('users', $user);
 *   $user['email'] = 'new@example.com'
 *   $db->update('users', $user, [
 *      'where' => 'name = :name',
 *      'bind' => ['name' => $name]
 *   ]);
 *   $db->remove('users', ['where' => 'name = :name', 'bind' => ['name' => $name]]);
 *
 *   $list = $db->get('Posts', [
 *       'where' => 'created_at like :date',
 *       'order_by' => 'created_at DESC, id DESC',
 *       'limit' => [0, 10],
 *       'bind' => ['date' => '%' . $_GET['date'] . '%']
 *   ]);
 *
 *   $last_post = $db->getFirst(['orderby' => 'date DESC']);
 */
class Database
{
    const NAME        = "Irfan's Database";
    const DESCRIPTION = "A bare-minimum and simple database connectivity";
    const VERSION     = "0.5";

    /**
     * @var DatabaseEngineInterface;
     */
    protected $engine = null;

    /**
     * List of available engines
     *
     * @var array
     */
    static protected $available_engines = [
        // todo -- add other engines (couchebase, MS-SQL, oracle ...)
        'mysql'  => '\IrfanTOOR\Database\Engine\MySQL',
        'sqlite' => '\IrfanTOOR\Database\Engine\SQLite',
    ];

    /**
     * Database constructor
     *
     * @param string $engine_name Database engine name e.g. 'sqlite', 'mysql' ...
     * @param array  $connection  Connection array containing the parameters required
     *                            by the Database Engines like MySQL, SQLite ...
     */
    public function __construct(?array $connection = null)
    {
        if (isset($connection['type']))
            $this->connect($connection);
    }

    /**
     * Connect to a Database Engine
     *
     * @param array $connection - Associative array of connection parameters
     *
     * @return bool Result indicates if the connect operation was successful
     */
    public function connect(array $connection): bool
    {
        $type   = strtolower($connection['type'] ?? 'unknown');
        $engine_name = self::$available_engines[$type] ?? null;

        if (!$engine_name)
            throw new Exception("Connectivity with: $type database type is not available");

        $this->engine = new $engine_name($connection);

        return $this->engine ? true : false;
    }

    /**
     * Returns the current Database Engine
     *
     * @return DatabaseEngineInterface | null
     */
    public function getDatabaseEngine()
    {
        return $this->engine;
    }

    /**
     * Passes all of the calls to Database Engine
     *
     * @param string $method Method
     * @param array  $args   Arguments to be passed
     *
     * @return mixed
     */
    public function __call(string $method, array $args)
    {
        if (!$this->engine)
            throw new Exception("No Database Engine is connected");

        if (method_exists($this->engine, $method))
            return call_user_func_array([$this->engine, $method], $args);

        throw new Exception("Method: $method, is not a valid method");
    }
}
