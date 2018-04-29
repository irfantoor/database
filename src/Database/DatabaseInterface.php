<?php

namespace IrfanTOOR\Database;

Interface DatabaseInterface {
    public function insert($record, $data);
    public function update($table, $data);
    public function remove($data);
    public function get($data = []);
    public function getFirst($data = []);
}
