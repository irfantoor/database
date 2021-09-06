<?php

namespace Tests;

use IrfanTOOR\Database\Model;

class Users extends Model
{
    function __construct($connection = [])
    {
        $this->schema = [
            'id'         => 'INTEGER PRIMARY KEY',
            'name'       => 'NOT NULL',
            'email'      => 'COLLATE NOCASE',
            'password'   => 'NOT NULL',
            'token',
            'validated'  => 'BOOL DEFAULT false',
            'created_on' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_on' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
        ];

        $this->indices = [
            ['index'  => 'name'],
            ['unique' => 'email'],
        ];

        parent::__construct($connection);
    }
}
