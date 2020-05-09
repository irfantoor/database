<?php
/**
 * IrfanTOOR\Database\Model
 * php version 7.3
 *
 * @package   IrfanTOOR\Database
 * @author    Irfan TOOR <email@irfantoor.com>
 * @copyright 2020 Irfan TOOR
 */
namespace IrfanTOOR\Database;

use Exception;
use IrfanTOOR\Database\SQLite;
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
     * Base url to be used during pagination
     *
     * @var string
     */
    protected $base_url = "/";

    /**
     * Number of results present per page
     *
     * @var int
     */
    protected $per_page = 10;

    /**
     * Interval of pages to be printed while preparing pagination
     *
     * @var int
     */
    protected $int_pages = 5;

    /**
     * Model constructor
     *
     * @param array $connection
     */
    function __construct(array $connection = [])
    {
        $this->file = $connection['file'] ?? null;

        if (!$this->file) {
            throw new Exception("file key is missing in the connection");
        } elseif (!is_string($this->file)) {
            throw new Exception("file key in the connection must be a string");
        } elseif (!file_exists($this->file)) {
            throw new Exception("file: {$this->file}, does not exist");
        }

        $table = $connection['table'] ?? null;

        # convert modelname to tablename e.g. Users::class will create the table 'users'
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
     * Prepares a schema of the datbase from model definition and returns it
     *
     * @return string Raw SQL schema, prepared from the definition of schema and
     *                indices, which were provided while wrinting the model 
     *                (ref: Creating a Model), is returned. This schema can be used
     *                to create the sqlite file manually.
     */
    function prepareSchema(): string
    {
        # Database structure
        $schema = 'CREATE TABLE IF NOT EXISTS ' . $this->table . ' (';
        $sep    = PHP_EOL;

        foreach ($this->schema as $fld => $def) {
            if (is_int($fld)) {
                $fld = $def;
                $def = '';
            }

            $schema .= $sep . $fld . ' ' . $def;
            $sep = ', ' . PHP_EOL;
        }

        $schema .= PHP_EOL . ');' . PHP_EOL;

        # Add indices
        foreach ($this->indices as $index) {
            foreach ($index as $type=>$field) {
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
     * Deploy the schema
     *
     * @param string $schema - The schema to be deployed to the connected file
     *
     * @throws Exception in case of error
     *
     * @return void
     */
    public function deploySchema(string $schema)
    {
        try {
            $schema = explode(';', str_replace(PHP_EOL, '', $schema));

            # removes the empty element at end
            array_pop($schema);

            # create the db structure
            $sql = array_shift($schema);
            $this->db->query($sql . ';');

            # create the indices now
            foreach ($schema as $sql) {
                $this->db->query($sql . ';');
            }
        } catch(Throwable $e) {
            throw new Exception($e->getMessage(), 1);
        }
    }

    /**
     * Inserts a record
     *
     * see DatabaseEngineInterface::insert for notes
     *
     * @param array $record - Associative array represnting one record
     * @param array $bind - The data we need to bind to the :placeholders in $record
     *
     * @return bool - result of the operation
     */
    public function insert(array $record, array $bind = [])
    {
        return $this->db->insert($this->table, $record, $bind);
    }

    /**
     * Insert or update a record
     *
     * see SQLite::insertOrUpdate for notes
     *
     * @param array $record - Associative array represnting one record
     * @param array $bind - The data we need to bind to the :placeholders in $record
     */
    public function insertOrUpdate(array $record, array $bind = [])
    {
        return $this->db->insertOrUpdate($this->table, $record, $bind);
    }

    /**
     * Update an existing record
     *
     * see DatabaseEngineInterface::update for notes
     *
     * @param array $record - Associative array represnting one record
     * @param array $options - The where clause or the binding data etc.
     */
    public function update(array $record, array $options = [])
    {
        return $this->db->update($this->table, $record, $options);
    }

    /**
     * Remove an existing record
     *
     * see DatabaseEngineInterface::remove for notes
     *
     * @param array $options - The where clause or the binding data etc.
     */
    public function remove(array $options)
    {
        return $this->db->remove($this->table, $options);
    }

    /**
     * Retrieve a list of records
     *
     * see DatabaseEngineInterface::get for notes
     *
     * @param array $options - The where clause, or the binding data etc.
     *                         this might include the order_by and limit parameters
     */
    public function get(array $options = [])
    {
        return $this->db->get($this->table, $options);
    }

    /**
     * Retrieve the first record
     *
     * see DatabaseEngineInterface::getFirst for notes
     *
     * @param array $options - The where clause or the binding data etc.
     *                         this might include the order_by and limit parameters
     */
    public function getFirst(array $options = [])
    {
        return $this->db->getFirst($this->table, $options);
    }

    /**
     * Verify if a record exists
     *
     * @param array $options - The where clause or the binding data etc.
     */
    public function has($options = [])
    {
        $r = $this->getFirst($options);
        return $r ? true : false;
    }

    /**
     * Set the base url
     *
     * @param string url - base url to be used while doing the pagination
     */
    public function setBaseUrl(string $url)
    {
        # todo -- validate url
        $this->base_url = $url;
    }

    /**
     * Set the number of entries per page
     *
     * @param int $per_page - number of entries to be displayed on a page
     */
    public function setPerPage(int $per_page)
    {
        $this->per_page = $per_page;
    }

    /**
     * Number of intermediate pages
     *
     * @param int $int_page - number of intermediate pages to be displayed in the
     *                        pagination bar
     */
    public function setIntermediatePages(int $int_pages)
    {
        if ($int_pages % 2 === 0) {
            $int_pages += 1;
        }

        $this->int_pages = $int_pages;
    }

    /**
     * Retrieve the pagination
     *
     * # todo -- add a parameter to do the reverse order pagination
     *
     * @param array $options - The where clause or the binding data etc.
     *
     * @return string - html block which can be displayed directly in an html page
     */
    public function getPagination($options = []): string
    {
        $per_page  = $this->per_page;

        $options['select'] = 'count(*)';
        $options['orderby'] = '';
        $options['limit'] = 1;

        $total = $this->getFirst($options)[0];
        $last  = ceil($total / $per_page);

        if ($last < 2)
            return '';

        $base_url = $this->base_url;
        $sep = strpos($base_url, '?') === false ? '?' : '&';
        $base_url .= $sep . 'page=';
        $int_pages = $this->int_pages;

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

    /**
     * Retrieve the reverse pagination
     *
     * @param array $options - The where clause or the binding data etc.
     *
     * @return string - html block which can be displayed directly in an html page
     */
    public function getReversePagination($options = []): string
    {
        $per_page  = $this->per_page;

        $options['select'] = 'count(*)';
        $options['orderby'] = '';
        $options['limit'] = 1;

        $total = $this->getFirst($options)[0];
        $last  = ceil($total / $per_page);

        if ($last < 2)
            return '';

        $base_url = $this->base_url;
        $sep = strpos($base_url, '?') === false ? '?' : '&';
        $base_url .= $sep . 'page=';
        $int_pages = $this->int_pages;

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
