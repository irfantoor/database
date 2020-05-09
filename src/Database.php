<?php
/**
 * IrfanTOOR\Database
 * php version 7.3
 *
 * @package   IrfanTOOR\Database
 * @author    Irfan TOOR <email@irfantoor.com>
 * @copyright 2020 Irfan TOOR
 */
namespace IrfanTOOR;

use Exception;
use IrfanTOOR\Database\{
    DatabaseEngineInterface,
    MySQL,
    SQLite
};

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
 *   $db->update('users', $user, ['where' => 'name = :name', 'bind' => ['name' => $name]]);
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
    /**
     * Pacakage name - Irfan's Database
     *
     * @var const
     */
    const NAME = "Irfan's Database";

    /**
     * Package description
     *
     * @var const
     */
    const DESCRIPTION = "A bare-minimum and simple database connectivity";

    /**
     * Package version
     *
     * @var const
     */
    const VERSION = "0.3.0"; // @@VERSION

    /**
     * Engine name
     *
     * @var string
     */
    protected $engine_name;

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
        # todo -- add other engines (couchebase, MS-SQL, oracle ...)
        'sqlite', 'mysql',
    ];
    
    /**
     * Database constructor
     *
     * @param string $engine_name Database engine name e.g. 'sqlite', 'mysql' ...
     * @param array  $connection  Connection array containing the parameters required
     *                            by the Database Engines like MySQL, SQLite ...
     */
    public function __construct(string $engine_name, ?array $connection = null)
    {
        $this->engine_name = strtolower($engine_name);

        if (!in_array($engine_name, self::$available_engines)) {
            throw new Exception("Connectivity with: $engine_name, is not available");
        }

        if ($connection) {
            $this->connect($connection);
        }
    }

    /**
     * Connect to a Database Engine
     *
     * @param  array  $connection  ['host' => 'localhost', 'user' => 'root', 'password' => 'toor', 'db_name' => 'main_db']
     *                             ['file' => '~/db/users.sqlite']
     * 
     * @return bool                result indicates if the connect operation was successful
     */
    public function connect(array $connection): bool
    {
        switch (strtolower($this->engine_name)) {
            case 'sqlite':
                $this->engine = new SQLite($connection);
                break;

            case 'mysql':
                $this->engine = new MySQL($connection);
                break;

            default:
        }

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
     * @param string
     * @param array
     * 
     * @return mixed
     */
    public function __call(string $method, array $args)
    {
        if (!$this->engine) {
            throw new Exception("No Database Engine is connected");
        }

        if (method_exists($this->engine, $method)) {
            return call_user_func_array([$this->engine, $method], $args);
        }

        throw new Exception("Method: $method, is not a valid method");
    }
}
