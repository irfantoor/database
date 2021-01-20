# Irfan's Database

Create models and/or access your databases with ease and least overhead.
A bare-minimum and simple database access.

## Creating a Database object

__method__: new Database(?array $connection = null)

__parameters__:
 - array  $connection  - Connection array containing the parameters required
 by the Database Engines like MySQL, SQLite ...

__returns__:
    Database object

__example__:
```php
<?php
$db = new Database(
    [
        'type' => 'sqlite',
        'file' => 'posts.sqlite'
    ]
);
```

## Connect to a Database Engine

__method__: connect(array $connection)

__parameteres__:
 - array $connection

__returns__:
 - true  - If the the database engine was successfully connected
 - false - If could not connect to the engine

__example__:
```php
<?php
# a database object is created, but it needs to be connected to a database
# before querring
$db = new Database();

# for sql
$connection = [
    'type' => 'sqlite',
    'file' => 'storage_path/users.sqlite',
];

# for mysql
$connection = [
    'type'     => 'mysql',
    'host'     => 'localhost',
    'user'     => 'root',
    'password' => 'toor',
    'db_name'  => 'test',
];

$db->connect($connection);

# Note: the definition of 'type' in connection is obligatory.
```

## Actions passed to database engine

### Executes a raw SQL

__method__: query(string $sql, array $data = \[])

__parameteres__:
 - string $sql,
 - array  $bind associative array to bind data in sql while preparing

__returns__:
 - true or false - if the query is of the type UPDATE, INSERT and DELETE
 - array - returns the result of the SELECT query

__example__:
```php
<?php
$result = $db->query('SECLECT count(*) from users where valid=true');
```

### Inserts a record into a connected database

__method__: insert(string $table, array $record, array $bind = \[])

__parameteres__:
 - string $table - The table to be queried
 - array  $record - associative array of record
```
Values might contain variables of the form :id etc, which are filled using the prepare 
mechanism, taking data from bind array e.g. ['id' => :id, 'name' => :name ]
```
 _Note: record must contain all of the required fields_
 - array  $bind - associative array e.g. ```['id' => $_GET['id'] ?? 1]```

__returns__:
- true - if the record was inserted
- false - record was not inserted

__example__:
```php
<?php
$db->insert('users', ['name' => 'Fabien Potencier', 'email' => 'fabien@symfony.com']);

# OR
$user = [
    'name' => 'Irfan TOOR',
    'email' => 'email@irfantoor.com',
    'password' => 'its-a-test',
];

$db->insert('users', $user);
# NOTE: the query will be prepared and values will be bound automatically
```

### Updates an existing record

__method__: update(string $table, array $record, array $options = \[])

__parameteres__:
 - string $table
 - array  $record  associated array only includes data to be updated
```
e.g $record = [
  'id'       => 1,
  'user'     => 'root',
  'password' => 'toor',
  'groups'   => 'admin,user,backup',
  'remote'   => false,
];
``` 
 - array  $options contains where, limit or bind etc.
```
 e.g $options = [
     'where' => 'id = :id', <------------+
     'limit' => 1,                       |
     'bind' => [                         |
         'id' => $_GET['root_id'] ?? 1, -+
     ]
 ];
```
 If options are not provided following are the assumed defaults:
  - 'where' => '1 = 1',
  - 'limit' => 1, // see DatabaseEngineInterface::get
  - 'bind'  => \[],

__returns__:
 - true  - if successful
 - false - otherwise

__example__:
```php
<?php
$db->update('users', 
    [
        'password' => $new_password,
    ],
    [
        'where' => 'email = :email',
        'bind'  => [
            'email' => $email
        ]
    ]
);
```

### Removes a record from database

__method__: remove(string $table, array $options)

__parameteres__:
 - string $table
 - array  $options contains where, limit or bind options
 If options are not provided following are the assumed defaults:
```
 [
     'where' => '1 = 0', # forces that a where be provided
     'limit' => 1,       # see DatabaseEngineInterface::get
     'bind'  => [],      # see DatabaseEngineInterface::update
 ]
```
__returns__: 
 - true - if removed successfully
 - false - otherwise

__example__:
```php
<?php
$db->remove(
    'users', 
    [
        'where' => 'email = :email', 
        'bind' => [
            'email' => $email
        ]
    ]
);
```

### Retreives list of records

__method__: get(string $table, array $options = \[])

__parameteres__:
 - string $table
 - array  $options - Associative array containing where, order_by, limit and bind

If limit is an int, the records are retrived from start, if its an array it is
interpretted like \[int $from, int $count], $from indicates number of records to
skip and $count indicates number of records to retrieve.
```
e.g. $options = [
  'limit' => 1 or 'limit' => [0, 10]
  'order_by' => 'ASC id, DESC date',
  'where' => 'date < :date', <---------------------------+
  'bind' => ['date' => $_POST['date'] ?? date('d-m-Y')], +
  # bind: see DatabaseEngineInterface::update
];
```
__returns__:

array \[row ...] containing the array of rows or null if not found

__example__:
```php
<?php
$list = $db->get('posts', [
    'where' => 'created_at like :date',
    'order_by' => 'created_at DESC, id DESC',
    'limit' => [0, 10],
    'bind' => ['date' => '%' . $_GET['date'] . '%']
]);
```

### Retreives only the first record

__method__: getFirst(string $table, array $options = \[]);

__parameteres__:
 - string $table   name of the table e.g. $table = 'useres';
 - array  $options as explained in DatabaseEngineInterface::get

__returns__:

array  containing the associative key=>value pairs of the row or null otherwise

__example__:
```php
<?php
$last_post = $db->getFirst('posts', ['orderby' => 'date DESC']);
```

## Database Models

__NOTE__: _Currently Models only supports SQLite db_

Models use the database and calls as explained above. Since a model is tied to a
table, therefore the same calls (of database) apply to a model except that the first prameter of
table_name is not present in the methods.

### Creating a model

__example__: Models\Users.php
```php
<?php
namespace Models\Users;

use IrfanTOOR\Database\Model;

class Users extends Model
{
    function __construct($connection)
    {
        # schema needs to be defined
        $this->schema = [
            'id'         => 'INTEGER PRIMARY KEY',

            'name'       => 'NOT NULL',
            'email'      => 'COLLATE NOCASE',
            'password'   => 'NOT NULL',
            'token',
            'validated'  => 'BOOL DEFAULT false',

            'created_on' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_on' => 'INTEGER'
        ];

        # indices need to be defined
        $this->indices = [
            ['index'  => 'name'],
            ['unique' => 'email'],
        ];

        # call the constructor with the $connection
        parent::__construct($connection);
    }
}
```

### Model constructor

__method__: $users = new Users(array $connection)

__parameteres__:
 - array $connection - \['file' => $db_path . 'users.sqlite', 'table' => 'users']

__returns__:

Users model object

__example__:
```php
<?php
use Model\Users;

$connection = [
    'file' => $db_path . 'users.sqlite',
    'table' => 'users'
];

# NOTE: If table name is not provided Model name e.g. 'Users' will be converted
#       to lowercase i.e. 'users' and will be used as table name.

$users = new Users($connection);
```

### Retrieves the name of the database file

__method__: getDatabaseFile()

__parameteres__: none

__returns__:

string - pathname of the sqlite file the model is connected to

__example__:
```php
<?php
$file =  $users->getDatabaseFile();
```

### Prepares a schema of the datbase from model definition and returns it

__method__: prepareSchema()

__parameteres__: none

__returns__:

 string - Raw SQL schema, prepared from the definition of schema and indices,
 which were provided while wrinting the model (ref: Creating a Model), is returned.
 This schema can be used to create the sqlite file manually.

__example__:
```php
<?php
$schema = $users->prepareSchema();
echo $schema;
```

### Deploy the schema

__method__: deploySchema(string $schema)

__parameteres__:
 - string $schema - The schema to be deployed to the connected file

__throws__: Exception - in case of error

__returns__: nothing

__example__:
```php
<?php
$file = $sb_path . 'users.sqlite';

# create a file and deploy the schema if it does not exist
if (!file_exists($file)) {
    file_put_contents($file, '');
    $users = new Users(['file' => $file]);
    $schema = $users->prepareSchema();
    $users->deploySchema($schema);
}
```

### Insert a record

__method__: insert(array $record, array $bind = \[])

__parameteres__:
 - array  $record Asociative array of record, 

 values might contain variables of the form :id etc, which are filled using the
 prepare mechanism, taking data from bind array e.g. \['id' => :id, 'name' => :name ]
 _Note: record must contain all of the required fields_

 - array $bind - The data we need to bind to the :placeholders in $record

__returns__:
 - true - if inserted the record successfully
 - false - otherwise

__example__:
```php
<?php
$user = [
    'name' => 'Irfan TOOR',
    'email' => 'email@irfantoor.com',
    'password' => 'some-password',
];

$users->insert($user);
```

### Insert or update a record

This method inserts the record if the record deoes not exist, or updates the
existing one.

__method__: insertOrUpdate(array $record, array $bind = \[])

__parameteres__:
 - array $record - Associative array represnting one record
 - array $bind - The data we need to bind to the :placeholders in $record

__returns__:
 - true - if inserted or updated the record successfully
 - false - otherwise

__example__:
```php
<?php
$user['password'] = 'password-to-be-updated';
$users->insertOrUpdate($user); # updates the record of previous example

$user = [
    'name' => 'Some User',
    'email' => 'email@example.com',
    'password' => 'some-password',
];

$users->insertOrUpdate($user); # inserts the record now
```

### Update an existing record

__method__: update(array $record, array $options = \[])

__parameteres__:
 - array $record - Associative array represnting one record
 - array $options - The where clause or the binding data etc.

__returns__:
 - true - if updated the record successfully
 - false - otherwise

__example__:
```php
<?php
$email = 'email@example.com';

$users->update(
    # only the record data which we need to modify
    [
        'password' => 'password',
    ],
    # options
    [
        'where' => 'email = :email',
        'bind' => [
            'email' => $email
        ]
    ]
);
```

### Remove an existing record

__method__: remove(array $options)

__parameteres__:
 - array $options - The where clause or the binding data etc.

__returns__:
 - true - if removed the record successfully
 - false - otherwise

__example__:
```php
<?php
$users->remove([
    'where' => 'email = :email',
    'bind' => [
        'email' => $email
    ]
]);
```

### Retrieve a list of records

__method__: get(array $options = \[])

__parameteres__:
 - array $options - The where clause or the binding data etc.

__returns__:
 array or records or null

__example__:
```php
<?php
$list = $users->get();
$list = $users->get(['where' => 'validated = true']);
$list = $posts->get(
    [
        'where' => 'created_at like :date',
        'order_by' => 'created_at DESC, id DESC',
        'limit' => [0, 10],
        'bind' => ['date' => '%' . $_GET['date'] . '%']
    ]
);
```

### Retrieve the first record

__method__: getFirst(array $options = \[])

__parameteres__:
 - array $options - The where clause or the binding data etc. this might include
 the order_by and limit parameters

__returns__:
 - array - an associative array containing the record
 - null - if could not find one

__example__:
```php
<?php
$user = $users->getFirst();
$last_post = $posts->getFirst(['orderby' => 'date DESC']);
```

### Verify if a record exists

__method__: has($options = \[])

__parameteres__:
 - array $options - The where clause or the binding data etc.

__returns__:
 - true - if record exists
 - false - otherwise

__example__:
```php
<?php
$users->has(
    [
        'where' => 'email = :email',
        'bind' => [
            'email' =>$email,
        ]
    ]
);
```
