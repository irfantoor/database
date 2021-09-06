<?php

use IrfanTOOR\Database\{
    Model,
    Engine\SQLite
};
use IrfanTOOR\Test;
use Tests\Users;

class ModelTest extends Test
{
    public function __construct()
    {
        $file = $this->getFile();

        # unlink the existing file
        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function getFile()
    {
        return __DIR__ . '/db/users.sqlite';
    }

    public function getUsers()
    {
        $file = $this->getFile();
        return new Users(['file' => $file, 'create' => true]);
    }

    public function testInstanceOfModel()
    {
        $users = $this->getUsers();

        $this->assertInstanceOf(Users::class, $users);
        $this->assertInstanceOf(Model::class, $users);
    }

    public function testModelConstructor()
    {
        $file = $this->getFile();

        # table name is provided
        $users = new Users(['file' => $file, 'table' => 'users_table']);
        $this->assertEquals('users_table',$this->getProperty($users, 'table'));

        # table deos not exist
        $this->assertException(
            function () use ($users) {
                $users->get();
            }
        );

        # table name is not provided
        # Model class is 'Users' => table name is strtolower of (classname)
        $users = new Users(['file' => $file]);
        $this->assertEquals('users', $this->getProperty($users, 'table'));
        $this->assertArray($users->get());
    }

    /**
     * throws: Exception::class
     * message: file is missing in the connection
     */
    function testModelConstructorExceptionNoFilename()
    {
        $u = new Users([]);
    }

    /**
     * throws: Exception::class
     * message: file must be a string
     */
    function testModelConstructorExceptionNotString()
    {
        $u = new Users(['file' => null]);
    }

    /**
     * throws: Exception::class
     * message: file: hello.sqlite, does not exist
     */
    function testModelConstructorExceptionDoesNotExist()
    {
        $u = new Users(['file' => "hello.sqlite"]);
    }

    function testAddField()
    {
        $file = $this->getFile();
        unlink($file);
        touch($file);

        $u = new Users(['file' => $file]);
        $u->addField('url', 'TEXT');

        $schema = $this->getProperty($u, 'schema');
        $this->assertTrue(array_key_exists('url', $schema));
        $this->assertEquals('TEXT', $schema['url']);
    }

    function testAddIndex()
    {
        $u = $this->getUsers();
        $indices = $this->getProperty($u, 'indices');
        $this->assertEquals(2, count($indices));

        $u->addIndex('created_on');  #adds an index
        $u->addIndex('token', true); #adds a unique index

        $indices = $this->getProperty($u, 'indices');
        $this->assertEquals(4, count($indices));
        $this->assertEquals(['index'  => 'created_on'], $indices[2]);
        $this->assertEquals(['unique' => 'token'], $indices[3]);
    }

    function testRemoveIndex()
    {
        $u = $this->getUsers();
        $u->removeIndex('name');
        $u->removeIndex('email');
        $schema = $this->getProperty($u, 'schema');
        $indices = $this->getProperty($u, 'indices');
        $this->assertTrue(array_key_exists('name', $schema));
        $this->assertTrue(array_key_exists('email', $schema));
        $this->assertEquals(0, count($indices));
    }

    function testRemoveField()
    {
        $u = $this->getUsers();
        $schema  = $this->getProperty($u, 'schema');
        $indices = $this->getProperty($u, 'indices');

        $this->assertTrue(array_key_exists('name', $schema));
        $this->assertEquals(['index' => 'name'], $indices[0]);
        $this->assertEquals(2, count($indices));

        # removes the field and the related index
        $u->removeField('name');
        $schema  = $this->getProperty($u, 'schema');
        $indices = $this->getProperty($u, 'indices');

        $this->assertFalse(array_key_exists('name', $schema));
        $this->assertNotEquals(['index' => 'name'], $indices[0]);
        $this->assertEquals(1, count($indices));
    }

    public function testModelGetFile()
    {
        $users = $this->getUsers();
        $this->assertEquals($this->getFile(), $users->getDatabaseFile());
    }

    public function testModelPrepareSchema()
    {
        $file = $this->getFile();
        unlink($file);
        file_put_contents($file, '');

        $users = $this->getUsers();
        $schema = $users->prepareSchema();

        foreach ($this->getProperty($users, 'schema') as $fld => $def) {
            if (is_int($fld)) {
                $fld = $def;
            };

            $this->assertTrue(strpos($schema, $fld) !== true);
        }

        foreach ($this->getProperty($users, 'indices') as $index) {
            foreach ($index as $type => $fld) {
                $find = strtoupper($type);
                if ($find !== 'INDEX') {
                    $find .= ' INDEX';
                }

                $find .= ' ' . 'users_' . $fld . '_' . $type . ' ON users(' .
                        $fld . ')';

                $this->assertTrue(strpos($schema, $find) !== false);
            }
        }
    }

    /**
     * throws: Exception::class
     * message: SQLSTATE[HY000]: General error: 1 no such table: users
     */
    public function testModelDeployNoTableException()
    {
        $users = $this->getUsers();

        # There is no table
        $r = $users->get();
    }

    /**
     * throws: Exception::class
     * message: SQLSTATE[HY000]: General error: 1 index users_name_index already exists
     */
    public function testModelReDeployException()
    {
        $users = $this->getUsers();

        # Deploy the schema
        $users->deploySchema();

        # try getting now
        $r = $users->get();
        $this->assertArray($r);
        $this->assertEquals([], $r);

        # try deploying on a database on which we have already deployed the schema
        $users->deploySchema();
    }

    public function testModelHas()
    {
        $users = $this->getUsers();

        # no data has been inserted so far!
        $this->assertFalse(
            $users->has(
                [
                'where' => 'id =:id',
                'bind'  => ['id' => 1],
                ]
            )
        );

        # No record in the database
        $this->assertFalse($users->has());

        $users->insert(
            [
            'name' => 'user-1',
            'email' => 'email1',
            'password' => 'password',
            ]
        );

        $this->assertTrue(
            $users->has(
                [
                'where' => 'email = :email',
                'bind'  => ['email' => 'email1']
                ]
            )
        );

        # database has records!
        $this->assertTrue($users->has());
    }

    public function testModelInsert()
    {
        $users = $this->getUsers();

        # insert user
        $users->insert(
            [
                'name' => 'user-2',
                'email' => 'email2',
                'password' => 'password',
            ]
        );

        # insert another user
        $users->insert(
            [
                'name' => 'user-3',
                'email' => 'email3',
                'password' => 'password',
            ]
        );

        # assert that the user exists now
        $this->assertTrue(
            $users->has(
                [
                'where' => 'name = :name',
                'bind'  => ['name' => 'user-1']
                ]
            )
        );

        $this->assertException(
            function () use ($users) {
                $users->insert(
                    [
                        'name'     => 'Someome',
                        'email'    => 'email2',
                        'password' => 'some pass',
                    ]
                );
            }
        );
    }

    public function testModelGet()
    {
        $users = $this->getUsers();

        # get the list of users
        $list = $users->get();

        # assert if count and the users name are as expected
        $this->assertEquals(3, count($list));
        $this->assertEquals('user-1', $list[0]['name']);
        $this->assertEquals('user-2', $list[1]['name']);
        $this->assertEquals('user-3', $list[2]['name']);
    }

    public function testModelGetFirst()
    {
        $users = $this->getUsers();

        # user with id 3 does not exist
        $user = $users->getFirst(
            [
            'where' => 'id = :id',
            'bind' => ['id' => 4]
            ]
        );

        # assert no user exists!
        $this->assertNull($user);

        # get the first user in reverse order
        $user = $users->getFirst(['orderby' => 'id desc']);

        # assert that we have a user and assert his expected name
        $this->assertNotNull($user);
        $this->assertEquals('user-3', $user['name']);
    }


    public function testModelUpdate()
    {
        $users = $this->getUsers();

        # id 1 exists
        $this->assertTrue($users->has(['where' => 'id = 1']));

        # get this record
        $user = $users->getFirst(['where' => 'id = 1']);

        # assert expected values
        $this->assertEquals('user-1',   $user['name']);
        $this->assertEquals('email1',   $user['email']);
        $this->assertEquals('password', $user['password']);

        # update password
        $users->update(
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

        $this->assertFalse($users->has(['where' => 'id = 1']));
        $user = $users->getFirst(['where' => 'id = 4']);
        $this->assertEquals('4', $user['id']);
        $this->assertEquals('email1', $user['email']);
        $this->assertEquals('updated password', $user['password']);

        # get the record
        $user = $users->getFirst(['where' => 'id = 4']);

        # assert expected values
        $this->assertEquals('user-1', $user['name']);
        $this->assertEquals('email1', $user['email']);
        $this->assertEquals('updated password', $user['password']);
    }

    public function testModelInsertOrUpdate()
    {
        $users = $this->getUsers();

        # id 1 does not exists, updated in a previous test
        $this->assertFalse(
            $users->has(['where' => 'id = 1'])
        );

        # it will do an insert as id 1 does not exists
        $result = $users->insertOrUpdate(
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
            $users->has(['where' => 'id = 1'])
        );

        # assert the expected values
        $user = $users->getFirst(['where' => "id = '1'"]);

        $this->assertEquals('inserted user', $user['name']);
        $this->assertEquals('inserted email', $user['email']);
        $this->assertEquals('inserted password', $user['password']);

        # it will update as id 1 already exists
        $result = $users->insertOrUpdate(
            [
                'id'       => 1,
                'name'     => 'updated user',
                'email'    => 'inserted email',
                'password' => 'updated password',
            ]
        );

        $this->assertTrue($result);

        # assert the expected values
        $user = $users->getFirst(['where' => 'id = 1']);

        $this->assertEquals('updated user',     $user['name']);
        $this->assertEquals('inserted email',   $user['email']);
        $this->assertEquals('updated password', $user['password']);
    }

    public function testModelRemove()
    {
        $users = $this->getUsers();

        $this->assertTrue($users->has(['where' => 'id = 4']));
        $users->remove(['where' => 'id = 4']);
        $this->assertFalse($users->has(['where' => 'id = 4']));
    }
}
