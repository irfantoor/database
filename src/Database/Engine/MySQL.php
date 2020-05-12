<?php
/**
 * IrfanTOOR\Database\Engine\MySQL
 * php version 7.3
 *
 * @package   IrfanTOOR\Database
 * @author    Irfan TOOR <email@irfantoor.com>
 * @copyright 2020 Irfan TOOR
 */

namespace IrfanTOOR\Database\Engine;

use Exception;
use IrfanTOOR\Database\{
    AbstractDatabaseEngine,
    DatabaseEngineInterface
};
use PDO;

/**
 * MySQL Database Engine
 */
class MySQL extends AbstractDatabaseEngine implements DatabaseEngineInterface
{
    /**
     * Connect to a database
     * 
     * @param array $connection Associative array giving connection parameters
     *              e.g. $connection = [
     *                  'host'     => '127.0.0.1',
     *                  'user'     => 'root',
     *                  'password' => 'toor',
     *                  'db_name'  => 'my_db',
     *              ];
     *
     * @return bool
     */
    function connect($connection = []): bool
    {
        $this->db = null;
        
        $host     = '127.0.0.1';
        $user     = '';
        $password = '';
        $db_name  = '';

        extract($connection);

        try {
            $this->db = new PDO(
                "mysql:host={$host};dbname={$db_name}", $user, $password
            );
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return $this->db ? true : false;
    }
}
