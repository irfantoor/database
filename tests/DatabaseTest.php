<?php
/**
 * DatabaseTest
 * php version 7.3
 *
 * @package   IrfanTOOR\Database
 * @author    Irfan TOOR <email@irfantoor.com>
 * @copyright 2020 Irfan TOOR
 */
use IrfanTOOR\Test;

use IrfanTOOR\Database;
use IrfanTOOR\Database\{
    DatabaseEngineInterface,
    AbstractDatabaseEngine,
    SQLite,
    MySQL
};

use Tests\Users;

class DatabaseTest extends Test
{
    public function __construct()
    {
        $file = $this->getFile();

        # unlink the existing file
        if (file_exists($file)) {
            unlink($file);
        }

        # reset the file
        file_put_contents($file, '');

        $users = $this->getUsers();

        $schema = $users->prepareSchema();
        $users->deploySchema($schema);
    }

    public function getFile()
    {
        return __DIR__ . '/db/users.sqlite';
    }

    public function getUsers()
    {
        return new Users([
            'file' => $this->getFile()
        ]);
    }

    public function connection($engine)
    {
        $connections = [
            'sqlite' => [
                'type' => 'sqlite',
                'file' => $this->getFile()
            ],

            'mysql' => [
                'type'     => 'mysql',
                'host'     => '127.0.0.1',
                'user'     => 'root',
                'password' => 'toor',
                'db_name'  => 'mydb',
            ],
        ];

        return $connections[$engine];
    }

    public function testInstanceOfDatabaseEngineInterface(): void
    {
        $db = new Database('sqlite');
        $this->assertInstanceOf(Database::class, $db);

        $engine = $db->getDatabaseEngine();
        $this->assertNull($engine);

        $db = new Database('sqlite', $this->connection('sqlite'));
        $engine = $db->getDatabaseEngine();

        $this->assertInstanceOf(Database::class, $db);
        $this->assertInstanceOf(SQLite::class, $engine);
        $this->assertImplements(DatabaseEngineInterface::class, $engine);
    }

    public function testConstants()
    {
        $db = new Database('mysql');

        $this->assertString($db::NAME);
        $this->assertString($db::DESCRIPTION);
        $this->assertString($db::VERSION);
    }

    public function testConnect()
    {
        $db = new Database('sqlite');
        $db->connect($this->connection('sqlite'));
        $engine = $db->getDatabaseEngine();

        $this->assertInstanceOf(SQLite::class, $engine);
        $this->assertImplements(DatabaseEngineInterface::class, $engine);

        $engine_name = 'UnknownEngine';

        $this->assertException(
            function () use ($engine_name) {
                $db = new Database($engine_name);
            },
            Exception::class,
            "Connectivity with: " . $engine_name . ", is not available",
        );
    }

    public function testCall()
    {
        $db = new Database('sqlite');

        $this->assertException(
            function () use ($db) {
                $db->hack('users');
            },
            Exception::class,
            "No Database Engine is connected"
        );

        $db->connect($this->connection('sqlite'));

        $this->assertException(
            function () use ($db) {
                $db->hack('users');
            },
            Exception::class,
            "Method: hack, is not a valid method"
        );
    }

    public function testInstanceOfMysql():void
    {
        $db = null;
        $msg = '';

        try {
            $db = new Database('mysql');
            $this->assertNull($db->getDatabaseEngine());
            $db->connect($this->connection('mysql'));

            // $this->assertInstanceOf(MySQL::class, $db->getDatabaseEngine());
        } catch(Throwable $e) {
            $msg = $e->getMessage();
            $expecting1 = "SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost' (using password: YES)";
            $expecting2 = "SQLSTATE[HY000] [2002] Connection refused";

            # tried to create a MySQL instance
            $this->assertTrue($expecting1 === $msg || $expecting2 === $msg);
        }
    }

    public function testDatabaseHas()
    {
        $db = new Database('sqlite', $this->connection('sqlite'));

        # no data has been inserted so far!
        $this->assertFalse(
            $db->has(
                'users', 
                [
                    'where' => 'id =:id',
                    'bind'  => ['id' => 1],
                ]
            )
        );

        # No record in the database
        $this->assertFalse($db->has('users'));

        $db->insert(
            'users', 
            [
                'name' => 'user-1',
                'email' => 'email1',
                'password' => 'password',
            ]
        );

        $this->assertTrue(
            $db->has(
                'users', 
                [
                    'where' => 'email = :email',
                    'bind'  => ['email' => 'email1']
                ]
            )
        );

        # database has records!
        $this->assertTrue($db->has('users'));
    }

    public function testDatabaseInsert()
    {
        $db = new Database('sqlite', $this->connection('sqlite'));

        # insert user
        $db->insert(
            'users',
            [
                'name' => 'user-2',
                'email' => 'email2',
                'password' => 'password',
            ]
        );

        # insert another user
        $db->insert(
            'users',
            [
                'name' => 'user-3',
                'email' => 'email3',
                'password' => 'password',
            ]
        );

        # assert that the user exists now
        $this->assertTrue(
            $db->has(
                'users', 
                [
                    'where' => 'name = :name',
                    'bind'  => ['name' => 'user-1']
                ]
            )
        );

        $this->assertException(
            function () use($db) {
                $db->insert(
                    'users',
                    [
                        'name'     => 'Someome',
                        'email'    => 'email2',
                        'password' => 'some pass',
                    ]
                );
            }
        );
    }

    public function testDatabaseGet()
    {
        $db = new Database('sqlite', $this->connection('sqlite'));

        # get the list of users
        $list = $db->get('users');

        # assert if count and the users name are as expected
        $this->assertEquals(3, count($list));
        $this->assertEquals('user-1', $list[0]['name']);
        $this->assertEquals('user-2', $list[1]['name']);
        $this->assertEquals('user-3', $list[2]['name']);
    }

    public function testDatabaseGetFirst()
    {
        $db = new Database('sqlite', $this->connection('sqlite'));

        # user with id 3 does not exist
        $user = $db->getFirst(
            'users',
            [
                'where' => 'id = :id',
                'bind' => ['id' => 4]
            ]
        );

        # assert no user exists!
        $this->assertNull($user);

        # get the first user in reverse order
        $user = $db->getFirst('users', ['order_by' => 'id desc']);

        # assert that we have a user and assert his expected name
        $this->assertNotNull($user);
        $this->assertEquals('user-3', $user['name']);
    }


    public function testDatabaseUpdate()
    {
        $db = new Database('sqlite', $this->connection('sqlite'));

        # id 1 exists
        $this->assertTrue($db->has('users', ['where' => 'id = 1']));

        # get this record
        $user = $db->getFirst('users', ['where' => 'id = 1']);

        # assert expected values
        $this->assertEquals('user-1',   $user['name']);
        $this->assertEquals('email1',   $user['email']);
        $this->assertEquals('password', $user['password']);

        # update password
        $db->update(
            'users',
            [
                'id' => 4,
                'password' => 'updated password'
            ], # record
            [
                'where' => 'id = :id',
                'bind'  => [
                    'id' => 1,
                ],
            ]
        );

        $this->assertFalse($db->has('users', ['where' => 'id = 1']));
        $user = $db->getFirst('users', ['where' => 'id = 4']);
        $this->assertEquals('4', $user['id']);
        $this->assertEquals('email1', $user['email']);
        $this->assertEquals('updated password', $user['password']);

        # get the record
        $user = $db->getFirst('users', ['where' => 'id = 4']);

        # assert expected values
        $this->assertEquals('user-1', $user['name']);
        $this->assertEquals('email1', $user['email']);
        $this->assertEquals('updated password', $user['password']);
    }

    public function testDatabaseInsertOrUpdate()
    {
        $db = new Database('sqlite', $this->connection('sqlite'));

        # id 1 does not exists, updated in a previous test
        $this->assertFalse(
            $db->has('users', ['where' => 'id = 1'])
        );

        # it will do an insert as id 1 does not exists
        $result = $db->insertOrUpdate(
            'users', 
            [
                'id'       => 1,
                'name'     => 'inserted user',
                'email'    => 'inserted email',
                'password' => 'inserted password',
            ]
        );

        $this->assertTrue($result);
        
        # id 1 is inserted as it exists now
        $this->assertTrue(
            $db->has('users', ['where' => 'id = 1'])
        );

        # assert the expected values
        $user = $db->getFirst('users', ['where' => "id = '1'"]);

        $this->assertEquals('inserted user', $user['name']);
        $this->assertEquals('inserted email', $user['email']);
        $this->assertEquals('inserted password', $user['password']);

        # it will update as id 1 already exists
        $result = $db->insertOrUpdate(
            'users',
            [
                'id'       => 1,
                'name'     => 'updated user',
                'email'    => 'inserted email',
                'password' => 'updated password',
            ]
        );

        $this->assertTrue($result);

        # assert the expected values
        $user = $db->getFirst('users', ['where' => 'id = 1']);

        $this->assertEquals('updated user',     $user['name']);
        $this->assertEquals('inserted email',   $user['email']);
        $this->assertEquals('updated password', $user['password']);
    }

    public function testDatabaseRemove()
    {
        $db = new Database('sqlite', $this->connection('sqlite'));

        $this->assertTrue($db->has('users', ['where' => 'id = 4']));
        $db->remove('users', ['where' => 'id = 4']);
        $this->assertFalse($db->has('users', ['where' => 'id = 4']));
    }

    public function testDatabaseQuery()
    {
        $db = new Database('sqlite', $this->connection('sqlite'));

        $result = $db->query(
            'SELECT count(*) from users where id < :max_id',
            [
                'max_id' => 10
            ]
        );

        $row1 = $result[0];
        $col1 = $row1[0];

        $this->assertTrue(is_array($result));
        $this->assertEquals(3, $col1);

        $result2 = $db->get(
            'users',
            [
                'where' => 'id < :max_id',
                'select' => 'count(*)',         # order is not important
                'bind' => [
                    'max_id' => 10,
                ]
            ]
        );

        $this->assertEquals($result, $result2);
    }
}
