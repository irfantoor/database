<?php

namespace IrfanTOOR\Database;

use Exception;
use IrfanTOOR\Database\SQLite;

/*
    !!! NOTE: Currently Model only supports SQLite db

    e.g.
    class Users extends IrfanTOOR\Database\Model
    {
        function __construct($connection)
        {
            $this->schema = [
                'id INTEGER PRIMARY KEY',

                'name NOT NULL',
                'email COLLATE NOCASE',
                'password NOT NULL',
                'token',
                'validated BOOL DEFAULT false',

                'created_on DATETIME DEFAULT CURRENT_TIMESTAMP',
                'updated_on INTEGER'
            ];

            $this->indecies = [
                ['index'  => 'name'],
                ['unique' => 'email'],
            ];

            parent::__construct($connection)
        }
    }

    $users = new Users(['file' => 'users.sqlite', 'table' => 'users']);
    $user->addOrUpdate(
        [
            'name' => 'Someone',
            'email' => 'someone@eample.com',
            'password' => 'Hello World!',
        ]
    );

    $user = $users->getFirst(
        ['where' => 'id = :id'],
        ['id' => $_GET['id']]
    );

    print_r($user['email']);

*/

class Model
{
    protected $schema;
    protected $indecies;
    protected $db;
    protected $file;
    protected $table;

    function __construct($connection = [])
    {
        $file  = isset($connection['file']) ? $connection['file'] : null;
        $table = isset($connection['table']) ? $connection['table'] : null;

        if (!$table) {
            $class = explode('\\', get_called_class());
            $table = strtolower(array_pop($class));
        }
        $this->table = $table;

        if (!$file) {
            $file = $table . '.sqlite';
        }
        $this->file = $file;

        try {
            $this->db    = new SQLite(['file' => $file]);
        } catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    function getFile()
    {
        return $this->file;
    }

    /**
     * Creates the schema of the DB according to the defined
     */
    function getSchema()
    {
        # Database structure
        $schema = 'CREATE TABLE IF NOT EXISTS ' . $this->table . ' (';
        $sep = PHP_EOL;

        foreach($this->schema as $fld) {
            $schema .= $sep . $fld;
            $sep = ', ' . PHP_EOL;
        }

        $schema .= PHP_EOL . ');' . PHP_EOL;

        # Indecies
        foreach($this->indecies as $index) {
            foreach($index as $type=>$field) {
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

    /*
     * Try to create the schema in the connected db
     *
     * @param $type
     */
    public function create()
    {
        try {
            $schema = explode(';', str_replace(PHP_EOL, '', $this->getSchema()));
            array_pop($schema);

            # db structure
            $sql = array_shift($schema);
            $this->db->query($sql . ';');

            # indecies
            foreach($schema as $sql) {
                $this->db->query($sql . ';');
            }
        } catch(Exception $e) {
            throw new Exception($e->getMessage(), 1);
        }
    }

    /*
     * Checks if the db has the record
     *
     * @param array $q    different elements of sql
     * @param array $data elements used for binding
     *
     * @return BOOL true if the record exists with the given conditions, false
     *              otherwise
     */
    public function has($q = [], $data = [])
    {
        $q['table'] = $this->table;
        return $this->db->has($q, $data);
    }

    /**
     * Gets the list as predfined by any previous functional operators
     *
     * @param array $q    different elements of sql
     * @param array $data elements used for binding
     *
     * @return array array of resulting records in which each record is an
     *               associated array, or an emty array.
     */
    public function get($q = [], $data = [], $pagination = null)
    {
        $q['table'] = $this->table;
        return $this->db->get($q, $data);
    }

    /**
     * Gets the first item as predfined by any previous functional operators
     *
     * @param array $q    different elements of sql
     * @param array $data elements used for binding
     *
     * @return array|null the first matching record as associated array or null
     */
     public function getFirst($q = [], $data = [])
     {
         $q['limit'] = 1;
         $list = $this->get($q, $data);

         return (count($list)) ? $list[0] : null;
     }

    /**
     * inserts a record
     *
     * @param array $data an assoicated array representing a record to be
     *                    inserted
     */
    public function insert($data)
    {
        return $this->db->insert($this->table, $data);
    }

    /**
     * inserts or updates a record
     *
     * @param array $data an assoicated array representing a record to be
     *                    inserted or updated. If no record is present with the
     *                    given details, a record is inserted or an existing is
     *                    updated
     */
    public function insertOrUpdate($data)
    {
        return $this->db->insertOrUpdate($this->table, $data);
    }

    /**
     * updates a record
     *
     * @param array $data an assoicated array representing a record to be
     *                    updated. Note id can not be changed to updated
     */
    public function update($q, $data)
    {
        $q['table'] = $this->table;
        return $this->db->update($q, $data);
    }

    /**
     * removes records
     *
     * @param array $q    different elements of sql
     * @param array $data elements used for binding
     */
    public function remove($q, $data = [])
    {
        $q['table'] = $this->table;
        $q['limit'] = 1;

        return $this->db->remove($q, $data);
    }

    /**
     * executes a sql statement, binding the provided variables
     *
     * @param string $sql  sql statement
     * @param array  $data array with associated elements for binding
     *
     * @return array array of resulting records in which each record is an
     *               associated array, or an emty array.
     */
    public function query($sql, $data=[])
    {
        return $this->db->query($sql, $data);
    }


    public function pagination($args = [], $q = [], $data = [])
    {
        $per_page  = isset($args['per_page']) ? $args['per_page'] : 10;
        $q['select'] = 'count(*)';
        $q['orderby'] = '';
        $q['limit'] = 1;

        $r = $this->getFirst($q, $data);
        $total = $r[0];
        $last  = ceil($total / $per_page);

        if ($last < 2)
            return '';

        $base_url = isset($args['base_url']) ? $args['base_url'] : '';
        $sep = strpos($base_url, '?') === false ? '?' : '&';
        $base_url .= $sep . 'page=';
        $int_pages = isset($args['int_pages']) ? $args['int_pages'] : 5;

        $first = 1;

        $current = (int) isset($_GET['page']) ? $_GET['page'] : 1;
        $current = $current < $first ? $first : $current;
        $current = $current > $last ? $last : $current;

        $prev = $current - 1;
        $prev = $prev ?: 0;

        $next = $current + 1;
        $next = $next > $last ? 0 : $next;

        $from = $current - ($int_pages - 1)/2;
        $from = $from > $first ? $from : $first;

        $to   = $from + $int_pages - 1;
        $to   = $to < $last ? $to : $last;

        $from = $to - $int_pages + 1;
        $from = $from > $first ? $from : $first;

        $first = $from == $first ? 0 : $first;
        $last = $to == $last ? 0 : $last;

        ob_start();
        echo PHP_EOL . '<ul class="pagination justify-content-center">' . PHP_EOL;

        if ($prev)
            echo '<li class="page-item"><a class="page-link" href="' . $base_url . $prev . '" rel="prev">&laquo</a></li>' . PHP_EOL;
        else
            echo '<li class="page-item disabled"><a class="page-link" href="#">&laquo;</a></li>' . PHP_EOL;

        if ($first) {
            echo '<li class="page-item"><a class="page-link" href="' . $base_url . $first . '">' . $first . '</a></li>' . PHP_EOL;
            if (($from - $first) > 1) {
                echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>' . PHP_EOL;
            }
        }

        for($i = $from; $i <= $to; $i++) {
            if ($i == $current)
                echo '<li class="page-item active"><a class="page-link" href="#">' . $current . '</a></li>'. PHP_EOL;
            else
                echo '<li class="page-item"><a class="page-link" href="' . $base_url . $i . '">' . $i . '</a></li>' . PHP_EOL;
        }

        if ($last) {
            if (($last - $to) > 1) {
                echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>' . PHP_EOL;
            }

            echo '<li class="page-item"><a class="page-link" href="' . $base_url . $last . '">' . $last . '</a></li>' . PHP_EOL;
        }

        if ($next)
            echo '<li class="page-item"><a class="page-link" href="' . $base_url . $next . '" rel="next">&raquo</a></li>' . PHP_EOL;
        else
            echo '<li class="page-item disabled"><a class="page-link" href="#">&raquo;</a></li>' . PHP_EOL;

        echo '</ul>' . PHP_EOL;

        return ob_get_clean();
    }

    public function paginationReverse($args = [], $q = [], $data = [])
    {
        $per_page  = $args['per_page'] ?: 10;
        $q['select'] = 'count(*)';
        $q['orderby'] = '';
        $q['limit'] = 1;

        $r = $this->getFirst($q, $data);
        $total = $r[0];
        $last  = ceil($total / $per_page);

        if ($last < 2)
            return '';

        $base_url = $args['base_url'] ?: '';
        $sep = strpos($base_url, '?') === false ? '?' : '&';
        $base_url .= $sep . 'page=';
        $int_pages = $args['int_pages'] ?: 5;

        $first = 1;

        $current = (int) isset($_GET['page']) ? $_GET['page'] : $last;
        $current = $current < $first ? $first : $current;
        $current = $current > $last ? $last : $current;

        $prev = $current - 1;
        $prev = $prev ?: 0;

        $next = $current + 1;
        $next = $next > $last ? 0 : $next;

        $from = $current - ($int_pages - 1)/2;
        $from = $from > $first ? $from : $first;

        $to   = $from + $int_pages - 1;
        $to   = $to < $last ? $to : $last;

        $from = $to - $int_pages + 1;
        $from = $from > $first ? $from : $first;

        $first = $from == $first ? 0 : $first;
        $last = $to == $last ? 0 : $last;

        ob_start();
        echo PHP_EOL . '<ul class="pagination justify-content-center">' . PHP_EOL;

        if ($next)
            echo '<li class="page-item"><a class="page-link" href="' . $base_url . $next . '" rel="next">&laquo</a></li>' . PHP_EOL;
        else
            echo '<li class="page-item disabled"><a class="page-link" href="#">&laquo;</a></li>' . PHP_EOL;

        if ($last) {
            echo '<li class="page-item"><a class="page-link" href="' . $base_url . $last . '">' . $last . '</a></li>' . PHP_EOL;
            if (($last - $to) > 1) {
                echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>' . PHP_EOL;
            }
        }

        for($i = $to; $i >= $from; $i--) {
            if ($i == $current)
                echo '<li class="page-item active"><a class="page-link" href="#">' . $current . '</a></li>'. PHP_EOL;
            else
                echo '<li class="page-item"><a class="page-link" href="' . $base_url . $i . '">' . $i . '</a></li>' . PHP_EOL;
        }

        if ($first) {
            if (($from - $first) > 1) {
                echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>' . PHP_EOL;
            }
            echo '<li class="page-item"><a class="page-link" href="' . $base_url . $first . '">' . $first . '</a></li>' . PHP_EOL;
        }

        if ($prev)
            echo '<li class="page-item"><a class="page-link" href="' . $base_url . $prev . '" rel="prev">&raquo</a></li>' . PHP_EOL;
        else
            echo '<li class="page-item disabled"><a class="page-link" href="#">&raquo;</a></li>' . PHP_EOL;


        echo '</ul>' . PHP_EOL;

        return ob_get_clean();
    }
}
