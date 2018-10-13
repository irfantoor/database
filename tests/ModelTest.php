<?php

use PHPUnit\Framework\TestCase;

use IrfanTOOR\Database\Model;

class Users extends Model
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

        parent::__construct($connection);
    }

    # only above definition is sufficient for development
    # the following two functions are for the tests only
    function schema()
    {
        return $this->schema;
    }

    function indecies()
    {
        return $this->indecies;
    }
}


class ModelTest extends TestCase
{
    protected $file;

    public function setup()
    {
        $this->file = __DIR__ . '/db/users.sqlite';
        if (!file_exists($this->file)) {
            file_put_contents($this->file, '');
        }
        $this->users = new Users(['file' => $this->file]);
    }

    public function testInstanceOfModel(): void
    {
        if (file_exists($this->file)) {
            unlink($this->file);
        }
        file_put_contents($this->file, '');

        $this->assertInstanceOf('IrfanTOOR\Database\Model', $this->users);
    }

    public function testModelGetFile(): void
    {
        $this->assertEquals($this->file, $this->users->getFile());
    }

    public function testModelGetSchema(): void
    {
        $schema = $this->users->getSchema();

        foreach($this->users->schema() as $fld) {
            $this->assertTrue(strpos($schema, $fld) !== false);
        }

        foreach($this->users->indecies() as $index) {
            foreach($index as $type => $fld) {
                $find = strtoupper($type);
                if ($find !== 'INDEX')
                    $find .= ' INDEX';

                $find .= ' ' . 'users_' . $fld . '_' . $type . ' ON users(' .
                        $fld . ')';

                $this->assertTrue(strpos($schema, $find) !== false);
            }
        }
    }

    public function testModelCreateOnNonExisting(): void
    {
        $e = null;
        $msg = '';
        try {
            $this->users->create();
        } catch(\Exception $e) {
            $msg = $e->getMessage();
        }
        $this->assertNull($e);
    }

    public function testModelCreateOnAlreadyExisting(): void
    {
        $e = null;
        $msg = '';
        try {
            $this->users->create();
        } catch(\Exception $e) {
            $msg = $e->getMessage();
        }
        $this->assertNotNull($e);
        $this->assertEquals($msg, 'SQLSTATE[HY000]: General error: 1 index users_name_index already exists');
    }

    public function testModelHas()
    {
        # no data has been inserted so far!
        $this->assertFalse(
            $this->users->has(
                ['where' => 'id =:id'],
                ['id' => 1]
            )
        );
    }

    public function testModelInsert()
    {
        # insert user
        $this->users->insert(
            [
                'id' => 1,
                'name' => 'user',
                'email' => 'email',
                'password' => 'password',
            ]
        );

        # insert another user
        $this->users->insert(
            [
                'id' => 2,
                'name' => 'another user',
                'email' => 'another email',
                'password' => 'another password',
            ]
        );

        # assert that the user exists now
        $this->assertTrue(
            $this->users->has(
                ['where' => 'id =:id'],
                ['id' => 1]
            )
        );
    }

    public function testModelGet()
    {
        # get the list of users
        $users = $this->users->get(
            ['limit' => 10]
        );

        # assert if count and the users name are as expected
        $this->assertEquals(2, count($users));
        $this->assertEquals('user', $users[0]['name']);
        $this->assertEquals('another user', $users[1]['name']);
    }

    public function testModelGetFirst()
    {
        # user with id 3 does not exist
        $user = $this->users->getFirst(
            ['where' => 'id = :id'],
            ['id' => 3]
        );

        # assert no user exists!
        $this->assertNull($user);

        # get the first user in reverse order
        $user = $this->users->getFirst(
            ['orderby' => 'id desc']
        );

        # assert that we have a user and assert his expected name
        $this->assertNotNull($user);
        $this->assertEquals('another user', $user['name']);
    }


    public function testModelUpdate()
    {
        # id 1 exists
        $this->assertTrue(
            $this->users->has(
                ['where' => 'id =:id'],
                ['id' => 1]
            )
        );

        # get this record
        $user = $this->users->getFirst(
            ['where' => 'id = :id'],
            ['id' => 1]
        );

        # assert expected values
        $this->assertEquals('user', $user['name']);
        $this->assertEquals('email', $user['email']);
        $this->assertEquals('password', $user['password']);

        # update password
        $this->users->update(
            [
                'where' => 'id = :id',
            ],
            [
                'id' => 1,
                'password' => 'updated password',
            ]
        );

        # get the record
        $user = $this->users->getFirst(
            ['where' => 'id = :id'],
            ['id' => 1]
        );

        # assert expected values
        $this->assertEquals('user', $user['name']);
        $this->assertEquals('email', $user['email']);
        $this->assertEquals('updated password', $user['password']);
    }

    public function testModelInsertOrUpdate()
    {

        # id 3 does not exists
        $this->assertFalse(
            $this->users->has(
                ['where' => 'id =:id'],
                ['id' => 3]
            )
        );

        # it will do an insert as id 3 does not exists
        $this->users->insertOrUpdate(
            [
                'id' => 3,
                'name' => 'inserted user',
                'email' => 'inserted email',
                'password' => 'inserted password',
            ]
        );

        # id 3 exists now
        $this->assertTrue(
            $this->users->has(
                ['where' => 'id =:id'],
                ['id' => 3]
            )
        );

        # assert the expected values
        $user = $this->users->getFirst(
            ['where' => 'id = :id'],
            ['id' => 3]
        );

        $this->assertEquals('inserted user', $user['name']);
        $this->assertEquals('inserted email', $user['email']);
        $this->assertEquals('inserted password', $user['password']);

        # it will update as id 3 already exists
        $this->users->insertOrUpdate(
            [
                'id' => 3,
                'name' => 'inserted user',
                'email' => 'inserted email',
                'password' => 'updated password',
            ]
        );

        # assert the expected values
        $user = $this->users->getFirst(
            ['where' => 'id = :id'],
            ['id' => 3]
        );

        $this->assertEquals('inserted user', $user['name']);
        $this->assertEquals('inserted email', $user['email']);
        $this->assertEquals('updated password', $user['password']);
    }

    public function testModelRemove()
    {
        # id 3 does not exists
        $this->assertTrue(
            $this->users->has(
                ['where' => 'id =:id'],
                ['id' => 3]
            )
        );

        # remove id 3
        $this->users->remove(
            ['where' => 'id = :id'],
            ['id' => 3]
        );

        # id 3 does not exists
        $this->assertFalse(
            $this->users->has(
                ['where' => 'id =:id'],
                ['id' => 3]
            )
        );
    }

    public function testModelQuery(): void
    {
        $result = $this->users->query(
            'SELECT count(*) from users where id < :max_id',
            [
                'max_id' => 10
            ]
        );

        $row1 = $result[0];
        $col1 = $row1[0];

        $this->assertTrue(is_array($result));
        $this->assertEquals(2, $col1);

        $result2 = $this->users->get(
            [
                'where' => 'id < :max_id',
                'select' => 'count(*)',         # order is not important
            ],
            [
                'max_id' => 10,
            ]
        );

        $this->assertEquals($result, $result2);
    }
}
