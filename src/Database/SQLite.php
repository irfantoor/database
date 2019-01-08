<?php

namespace IrfanTOOR\Database;

use Exception;
use PDO;

/*
    $db = new Database\SQLite(['file' => 'hello.sqlite']);

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

class SQLite extends AbstractDatabase
{

    /*
     * Constructs the SQLite Object
     *
     * @param array|string $file
     */
    function __construct($connection = [])
    {
        $file = '';
        extract($connection);

        if (!file_exists($file))
            throw new Exception("sqlite file [$file] does not exist");

        try {
            $this->db = new PDO('sqlite:' . $file);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }

        $this->_init();
    }

    /**
     * inserts or update a record
     *
     * @param array $record
     */
    public function insertOrUpdate($tableORq, $data)
    {
        if (is_string($tableORq)) {
            $this->defaults['table'] = $tableORq;
            $tableORq = [];
        }
        extract($this->defaults);
        extract($tableORq);

        if (!$table)
            throw new Exception("table [$tabel] not defined", 1);

        $sql =  'INSERT OR REPLACE INTO ' . $table . ' ' .
                '(' . implode(', ', array_keys($data)) . ') ' .
                'VALUES ( :' . implode(', :', array_keys($data)) . ');';

        return $this->query($sql, $data);
    }
}
