# Irfan's Database

Create models and/or access your databases with ease and least overhead.
A bare-minimum and simple database access.

## Creating a Database object

method: new Database(string $engine_name, ?array $connection = null)

parameters:
    string $engine_name - Database engine name e.g. 'sqlite', 'mysql' ...
    array  $connection  - Connection array containing the parameters required
                          by the Database Engines like MySQL, SQLite ...
returns:
    Database object

example:
```php
  $db = new Database('sqlite', ['file' => 'posts.sqlite']);
```

## Connect to a Database Engine

method: connect(array $connection)

parameteres:
    array $connection

returns:
    true  - If the the database engine was successfully connected
    false - If could not connect to the engine

examples:
```php
  $db = new Database('sqlite');
  $db->connect(['file' => 'posts.sqlite']);

  # for sql
  $connection = [
      'file' => 'storage_path/users.sqlite',
  ];

  # for mysql
  $connection = [
      'host'     => 'localhost',
      'user'     => 'root',
      'password' => 'toor',
      'db_name'  => 'test',
  ];
```

## Actions passed to database engine

### Executes a raw SQL

method: query(string $sql, array $data = [])

parameters:
    string $sql,
    array  $bind associative array to bind data in sql while preparing

returns:
    true or false - if the query is of the type UPDATE, INSERT and DELETE
    array - returns the result of the SELECT query

example:
```php
    $result = $db->query('SECLECT count(*) from users where valid=true');
```

### Inserts a record into a connected database

method: insert(string $table, array $record, array $bind = [])

parameteres:
    string $table - The table to be queried
    array  $record - associative array of record, values might contain
        variables of the form :id etc, which are filled using
        the prepare mechanism, taking data from bind array
        e.g. ['id' => :id, 'name' => :name ]
        Note: record must contain all of the required fields
    array  $bind - associative array e.g. ['id' => $_GET['id'] ?? 1]

returns:
    true - if the record was inserted
    false - record was not inserted

example:
```php
    $db->insert('users', ['name' => 'Fabien Potencier', 'email' => 'fabien@symfony.com']);

    # or
    $user = [
        'name' => 'Irfan TOOR',
        'email' => 'email@irfantoor.com',
        'password' => 'its-a-test',
    ];

    $db->insert('users', $user);

    # NOTE: the query will be prepared and values will be bound automatically
```

### Updates an existing record

method: update(string $table, array $record, array $options = [])

parameters:
    string $table
    array  $record  associated array only includes data to be updated
        e.g $record = [
                'id'       => 1,
                'user'     => 'root', 
                'password' => 'toor',
                'groups'   => 'admin,user,backup',
                'remote'   => false,
            ];
    array  $options contains where, limit or bind etc.
        e.g $options = [
                'where' => 'id = :id', <------------+
                'limit' => 1,                       |
                'bind' => [                         |
                    'id' => $_GET['root_id'] ?? 1, -+
                ]
            ];
        If options are not provided following are the assumed defaults:
            'where' => '1 = 1',
            'limit' => 1, // see DatabaseEngineInterface::get
            'bind'  => [],

returns:
    true  - if successful
    false - otherwise

example:
```php
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

method: remove(string $table, array $options)

parameters:
    string $table
    array  $options contains where, limit or bind options
        If options are not provided following are the assumed defaults:
        [
            'where' => '1 = 0', # forces that a where be provided
            'limit' => 1,       # see DatabaseEngineInterface::get
            'bind'  => [],      # see DatabaseEngineInterface::update
        ]

returns: 
    true - if removed successfully
    false - otherwise

example:
```php
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

method: get(string $table, array $options = [])

parameters:
    string $table
    array  $options 
        associated array containing where, order_by, limit and bind
        if limit is an int, the records are retrived from start, if its
        an array it is interpretted like [int $from, int $count], $from
        indicates number of records to skip and $count indicates number
        of records to retrieve.
        e.g. $options = [
                    'limit' => 1 or 'limit' => [0, 10]
                    'order_by' => 'ASC id, DESC date',
                    'where' => 'date < :date', <---------------------------+
                    'bind' => ['date' => $_POST['date'] ?? date('d-m-Y')], +
                    # bind: see DatabaseEngineInterface::update
                ];
 
returns:
    array [row ...] containing the array of rows or null if not found

example:
```php
  $list = $db->get('posts', [
      'where' => 'created_at like :date',
      'order_by' => 'created_at DESC, id DESC',
      'limit' => [0, 10],
      'bind' => ['date' => '%' . $_GET['date'] . '%']
  ]);
```

### Retreives only the first record

method: getFirst(string $table, array $options = []);

parameters:
    string $table   name of the table e.g. $table = 'useres';
    array  $options as explained in DatabaseEngineInterface::get

returns:
    array  containing the associative key=>value pairs of the row or null otherwise

example:
```php
    $last_post = $db->getFirst('posts', ['orderby' => 'date DESC']);
```

## Database Models

NOTE: Currently Models only supports SQLite db

Models use the database and calls as explained above. Since a model is tied to a
table, therefore the same calls apply to a model except that the first prameter of
table_name is not present in the methods.

### Creating a model

example: Models\Users.php
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

method: $users = new Users(array $connection)

parameters:
    array $connection - ['file' => $db_path . 'users.sqlite', 'table' => 'users']

returns:
    Users model object

example:
```php
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

method: getDatabaseFile()

parameters: none

returns:
    string - pathname of the sqlite file the model is connected to

example:
```php
    $file =  $users->getDatabaseFile();
```

### Prepares a schema of the datbase from model definition and returns it

method: prepareSchema()

parameters: none

returns:
    string - Raw SQL schema, prepared from the definition of schema and indices,
             which were provided while wrinting the model (ref: Creating a Model),
             is returned. This schema can be used to create the sqlite file manually.

example:
```php
    $schema = $users->prepareSchema();
    echo $schema;
```

### Deploy the schema

method: deploySchema(string $schema)

parameters:
    string $schema - The schema to be deployed to the connected file

throws:
    Exception - in case of error

example:
```php
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

method: insert(array $record, array $bind = [])

parameters:
    array  $record  associative array of record, values might contain
                    variables of the form :id etc, which are filled using
                    the prepare mechanism, taking data from bind array
                    e.g. ['id' => :id, 'name' => :name ]
                    Note: record must contain all of the required fields
    array $bind - The data we need to bind to the :placeholders in $record

returns:
    true - if inserted the record successfully
    false - otherwise

example:
```php
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

method: insertOrUpdate(array $record, array $bind = [])

parameters:
    array $record - Associative array represnting one record
    array $bind - The data we need to bind to the :placeholders in $record

returns:
    true - if inserted or updated the record successfully
    false - otherwise

example:
```php
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

method: update(array $record, array $options = [])

parameters:
    array $record - Associative array represnting one record
    array $options - The where clause or the binding data etc.

returns:
    true - if updated the record successfully
    false - otherwise

example:
```php
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

method: remove(array $options)

parameters:
    array $options - The where clause or the binding data etc.

returns:
    true - if removed the record successfully
    false - otherwise

example:
```php
    $users->remove([
        'where' => 'email = :email',
        'bind' => [
            'email' => $email
        ]
    ]);
```

### Retrieve a list of records

method: get(array $options = [])

parameters:
    array $options - The where clause or the binding data etc.

returns:
    array or records or null

example:
```php
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

method: getFirst(array $options = [])

parameters:
    array $options - The where clause or the binding data etc.
                     this might include the order_by and limit parameters

returns:
    array - an associative array containing the record
    null - if could not find one

example:
```php
    $user = $users->getFirst();
    $last_post = $posts->getFirst(['orderby' => 'date DESC']);
```

### Verify if a record exists

method: has($options = [])

parameters:
    array $options - The where clause or the binding data etc.

returns:
    true - if record exists
    false - otherwise

example:
```php
    $users->has(
        [
            'where' => 'email = :email',
            'bind' => [
                'email' =>$email,
            ]
        ]
    );
```

## Model Pagination

### Set the base url

method: setBaseUrl(string $url)

parameters:
    string url - base url to be used while doing the pagination
                 default is '/'

example:
```php
    $users->setBaseUrl('/users//');
```

### Set the number of entries per page

method: setPerPage(int $per_page)

parameters:
    int $per_page - number of entries to be displayed on a page
                    default is 10

example:
```php
    $per_page = 100;
    $users->setPerPage($per_page);
```

### Number of intermediate pages

method: setIntermediatePages(int $int_pages)

parameters:
    int $int_page - number of intermediate pages to be displayed in the
                    pagination bar, default is 5 (should always be odd)

example:
```php
    $users->setIntermediatePages(7);
```

### Retrieve the pagination

method: getPagination($options = [])

parameters:
    array $options - The where clause or the binding data etc.

returns:
    string - html block which can be displayed directly in an html page

example:
```php
    $page = 1;
    $from = ($page - 1) * $per_page;

    $options = [
        'limit' => [$from, $per_page]
        'where' => 'validated = true'
    ];

    $list = $users->get($options);
    # you can use templating to display this list

    $pagination = $users->getPagination($options);
    # this can directly displyed

    echo $pagination;
```

### Retrieve the reverse pagination

Returns the pagination in reverse order. It was created to make a page number
show always the same content, despite newer entries being added.

method: getReversePagination($options = [])

parameters:
    array $options - The where clause or the binding data etc.

returns:
    string - html block which can be displayed directly in an html page

example:
```php
    $page = 1;
    $from = ($page - 1) * $per_page;

    $options = [
        'limit' => [$from, $per_page]
        'where' => 'validated = true'
    ];

    $list = $users->get($options);
    # you can use templating to display this list

    $pagination = $users->getReversePagination($options);
    # this can directly displyed

    echo $pagination;
```
}
