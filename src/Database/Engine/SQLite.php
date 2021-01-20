<?php

/**
 * IrfanTOOR\Database\Engine\SQLite
 * php version 7.3
 *
 * @author    Irfan TOOR <email@irfantoor.com>
 * @copyright 2021 Irfan TOOR
 */

namespace IrfanTOOR\Database\Engine;

use Exception;
use IrfanTOOR\Database\{
    AbstractDatabaseEngine,
    DatabaseEngineInterface
};
use PDO;

/**
 * SQLite Database Engine
 */
class SQLite extends AbstractDatabaseEngine implements DatabaseEngineInterface
{
    /**
     * Connect to a database
     * 
     * @param array $connection Associative array giving connection parameters
     *              e.g. $connection = [
     *                  'file'     => $db_storage_path . 'users.sqlite',
     *              ];
     *
     * @return bool
     */
    function connect($connection = []): bool
    {
        $this->db = null;
        $file = $connection['file'] ?? null;

        if (!$file) {
            throw new Exception("connection does not include the file definition");
        }

        if (!file_exists($file)) {
            throw new Exception("sqlite file: $file, does not exist");
        }

        try {
            $this->db = new PDO('sqlite:' . $file);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }

        return $this->db ? true : false;
    }

    /**
     * Insert a record into a connected database, or or updates an existing record
     * 
     * @param string $table  Table name
     * @param array  $record associative array of record, values might contain
     *                       variables of the form :id etc, which are filled
     *                       using the prepare mechanism, taking data from
     *                       bind array e.g. ['id' => :id, 'name' => :name ]
     *                       Note: record must contain all of the required
     *                       fields
     * @param array  $bind   associative array e.g. ['id' => $_GET['id'] ?? 1], 
     *                       see DatabaseEngineInterface::update for bind
     *                       details
     * 
     * @return bool result of the insert/update operation
     */
    public function insertOrUpdate(string $table, array $record, array $bind = [])
    {
        extract(self::$defaults, EXTR_SKIP);

        $sql =  'INSERT OR REPLACE INTO ' . $table . ' ' .
               '(' . implode(', ', array_keys($record)) . ') VALUES (';

        $sep = '';

        foreach ($record as $k => $v) {
            if (isset($bind[$k])) {
                $sql .= $sep . ':__' . $k;
                $bind['__' . $k] = $v;
            } else {
                $sql .= $sep . ':' . $k;
            }
            
            $sep = ', ';
        }

        $sql .= ');';

        foreach ($record as $k => $v) {
            if (!isset($bind[$k])) {
                $bind[$k] = $v;
            }
        }

        return $this->query($sql, $bind);
    }
}
