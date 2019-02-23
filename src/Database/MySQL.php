<?php

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


class MySQL extends AbstractDatabase
{
    protected $schema;
    protected $indecies;

    function __construct($connection=[])
    {
        $host = '127.0.0.1';
        $user = 'root';
        $password = 'Hello World!';
        $dbname = 'it_';

        extract($connection);
        try {
            # throw new \Exception("mysql:host={$host};dbname={$dbname}; $user, $password");
            $this->db = new PDO("mysql:host={$host};dbname={$dbname}", $user, $password);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        $this->_init();
    }
}
