<?php

namespace IrfanTOOR;

use IrfanTOOR\Database\MySQL;
use IrfanTOOR\Database\SQLite;

class Database
{
    protected $adapter;
    
    public function __construct($connection)
    {
        if (isset($connection['file'])) {
            # Its a SQLite
            $this->adapter = new SQLite($connection);
        } else {
            $this->adapter = new MySQL($connection);
        }
    }
    
    public function adapter()
    {
        return $this->adapter;
    }

    public function __call($method, $args)
    {
        if (method_exists($this->adapter, $method)) {
            return call_user_func_array([$this->adapter, $method], $args);
        }

        throw new \Exception("Method $method is not a valid method");
    }    
}