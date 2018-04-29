<?php

namespace IrfanTOOR\Database;

use Exception;
use PDO;

/*

    $db = new Database(['file' => 'posts.sqlite']);
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

Abstract class AbstractDatabase
{
    protected $db;
    protected $defaults;

    public function _init()
    {
        $this->defaults = [
            'table'        => '',
            'select'       => '*',
            'where'        => '',
            'orderby'      => '',
            'limit'        => 10,
            'offset'       => 0,
        ];
    }

    public function has($q = [], $data = [])
    {
        $item = $this->getFirst($q, $data);
        return $item ? true : false;
    }

    /**
     * Gets the list as predfined by any previous functional operators
     *
     */
    public function get($q = [], $data = [])
    {
        extract($this->defaults);
        extract($q);

        if (!$table)
            throw new Exception("table [$tabel] not defined", 1);


        if (is_array($limit)) {
            $offset = $limit[1];
            $limit = $limit[0];
        }

        $sql =
            'SELECT '    . $select .
            ' FROM '     . $table .
            ($where ?   ' WHERE '    . $where   : '' ).
            ($orderby ? ' ORDER BY ' . $orderby : '' ).
            ' LIMIT '    . $offset . ',' . $limit;

        return $this->query($sql, $data);
    }

    /**
     * Gets the first item as predfined by any previous functional operators
     *
     * @param array $q
     * @param array $data
     * 
     * @result
     */
     public function getFirst($q=[], $data = [])
     {
         $q['limit'] = 1;
         $list = $this->get($q, $data);

         return (count($list)) ? $list[0] : null;
     }

    /**
     * inserts a record
     *
     * @param string|array $q    name of table or array containg additional 
     *                           information as well
     * @param array        $data associative array with binding data
     */
    public function insert($q, $data)
    {
        if (is_string($q)) {
            $this->defaults['table'] = $q;
            $q = [];
        }
        extract($this->defaults);
        extract($q);

        if (!$table)
            throw new Exception("table [$tabel] not defined", 1);

        $sql =  'INSERT INTO ' . $table . ' ' .
                '(' . implode(', ', array_keys($data)) . ') ' .
                'VALUES ( :' . implode(', :', array_keys($data)) . ');';

        return $this->query($sql, $data);
    }

    /**
     * updates a record
     *
     * @param string|array $q    name of table or array containg additional 
     *                           information as well
     * @param array        $data associative array with binding data
     */
    public function update($q, $data)
    {
        if (is_string($q)) {
            $this->defaults['table'] = $q;
            $q = [];
        }
        extract($this->defaults);
        extract($q);

        if (!$table)
            throw new Exception("table [$tabel] not defined", 1);

        if (!$limit)
            $limit = 1;

        $sql =  'UPDATE ' . $table . ' SET ';
        $sep = '';
        foreach ($data as $k=>$v) {
            if ($k == 'id')
                continue;

            $sql .= $sep . "$k = :$k";
            $sep = ', ';
        }
        $sql .=
            ($where ?   ' WHERE '    . $where   : '' );
            # ' LIMIT '    . $limit;

        return $this->query($sql, $data);
    }

    /**
     * removes records
     *
     * @param string|array $q    name of table or array containg additional 
     *                           information as well
     * @param array        $data associative array with binding data
     */
    public function remove($q, $data = [])
    {
        if (is_string($q)) {
            $this->defaults['table'] = $q;
            $q = [];
        }
        extract($this->defaults);
        extract($q);

        if (!$where)
            throw new Exception("where condition is required", 1);

        if (!$table)
            throw new Exception("table [$tabel] not defined", 1);

        if (!$limit)
            $limit = 1;

        $sql =  'DELETE FROM ' . $table;
        $sql .=
            ' WHERE ' . $where;
            # ' LIMIT '    . $limit;

        return $this->query($sql, $data);
    }

     /**
      * Verifies if a condition can return records
      *
      * @param string $where
      */
     public function query($sql, $data = [])
     {
         $this->_init();

         try {
             $q = $this->db->prepare($sql);
         } catch (PDOException $e) {
             throw new Exception($e->getMessage());
         }

         foreach ($data as $k => $v) {
             $$k = $v;
             $q->bindParam(':' . $k, $$k); # bindParam( ... , PDO::PARAM_INT ...);
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
}
