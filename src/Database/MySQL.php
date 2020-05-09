<?php
/**
 * IrfanTOOR\Database\MySQL
 * php version 7.3
 *
 * @package   IrfanTOOR\Database
 * @author    Irfan TOOR <email@irfantoor.com>
 * @copyright 2020 Irfan TOOR
 */
namespace IrfanTOOR\Database;

use Exception;
use PDO;

/*

    $db = new Database\MySQL(
        [
            'host'     => 'localhost',
            'user'     => 'root',
            'password' => 'toor',
            'dbname'   => 'mysite',
        ]
    );

    $list = $db->get(
        [
            'table' => 'Posts',
            'where' => 'created_at like :date',
            'order_by' => 'crated_at DESC, id DESC',
            'limit' => 10,
        ],
        [
            'date' => '%' . $_GET['date'] . '%'
        ]
    );

    $post = $db->getFirst(['orderby' => 'id DESC', 'limit' => 1]);

*/


class MySQL extends AbstractDatabaseEngine implements DatabaseEngineInterface
{
    /**
     * Connect to a database
     * 
     * @param array $connection e.g. $connection = [
     *                                   'host'     => '127.0.0.1',
     *                                   'user'     => 'root',
     *                                   'password' => 'toor',
     *                                   'db_name'  => 'my_db',
     *                               ];
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
            $this->db = new PDO("mysql:host={$host};dbname={$db_name}", $user, $password);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return $this->db ? true : false;
    }
}
