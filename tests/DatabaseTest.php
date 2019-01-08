<?php

use IrfanTOOR\Test;

use IrfanTOOR\Database;
use IrfanTOOR\Database\DatabaseInterface;

class DatabaseTest extends Test
{
    public function connections()
    {
        return [
            ['file' => __DIR__ . '/db/users.sqlite'],
            [
                'host'     => '127.0.0.1',
                'user'     => 'root',
                'password' => 'toor',
                'dbname'   => 'mysql',
            ],
        ];
    }

    public function setup()
    {
        $file = $this->connections()[0]['file'];
        if (file_exists($file)) {
            unlink($file);
        }
        file_put_contents($file, '');
    }

    public function testInstanceOfDatabaseInterface(): void
    {
        $db = new Database($this->connections()[0]);
        $this->assertInstanceOf('IrfanTOOR\Database\SQLite', $db->adapter());
        $this->assertImplements('IrfanTOOR\Database\DatabaseInterface', $db->adapter());
    }

    public function testInstanceOfSqlite():void
    {
        $db = new Database($this->connections()[0]);
        $this->assertInstanceOf('IrfanTOOR\Database\SQLite', $db->adapter());
    }

    public function testInstanceOfMysql():void
    {
        $db = null;
        $msg = '';
        try {
            $db = new Database($this->connections()[1]);
            $this->assertInstanceOf('IrfanTOOR\Database\MySQL', $db->adapter());
        } catch(\Exception $e) {
            $msg = $e->getMessage();
            $expecting1 = "SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost' (using password: YES)";
            $expecting2 = "SQLSTATE[HY000] [2002] Connection refused";

            # tried to create a MySQL instance
            $this->assertTrue($expecting1 === $msg || $expecting2 === $msg);
        }
    }
}
