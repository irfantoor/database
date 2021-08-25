<?php

/**
 * IrfanTOOR\Database\DatabaseEngineInterface
 * php version 7.3
 *
 * @author    Irfan TOOR <email@irfantoor.com>
 * @copyright 2021 Irfan TOOR
 */

namespace IrfanTOOR\Database\Engine;

/**
 * Defines the DatabaseEngineInterface
 */
Interface DatabaseEngineInterface
{
    /**
     * Connect to a database
     *
     * @param array $connection Associative array of connection parameters
     *
     * @return bool
     */
    public function connect(array $connection): bool;

    /**
     * Executes a raw SQL
     *
     * @param string $sql  Raw SQL, might contain :placeholders
     * @param array  $bind Associative array to bind data in sql while preparing
     *                     see DatabaseEngineInterface::update
     *
     * @return mixed
     */
    public function query(string $sql, array $bind = []);

    /**
     * Inserts a record into a connected database
     *
     * @param string $table  Table name
     * @param array  $record Associative array of record, values might contain
     *                       variables of the form :id etc, which are filled
     *                       using the prepare mechanism, taking data from
     *                       bind array e.g. ['id' => :id, 'name' => :name ]
     *                       Note: record must contain all of the required
     *                       fields
     * @param array  $bind   Associative array e.g. ['id' => $_GET['id'] ?? 1],
     *                       see DatabaseEngineInterface::update for bind
     *                       details
     *
     * @return bool Result of the insert operation
     */
    public function insert(string $table, array $record, array $bind = []);

    /**
     * Updates an existing record
     *
     * @param string $table   Table name
     * @param array  $record  Associative array only includes data to be updated
     * @param array  $options Contains where, limit or bind etc.
     *
     * @return bool result of the update operation
     */
    public function update(string $table, array $record, array $options = []);

    /**
     * Removes a record from database
     *
     * @param string $table   Table name
     * @param array  $options Contains where, limit or bind options
     *
     * @return bool result of the update operation
     */
    public function remove(string $table, array $options);

    /**
     * Retreives list of records
     *
     * @param string $table   Table name
     * @param array  $options Associative array containing where, order_by, limit and
     *                        bind if limit is an int, the records are retrived from
     *                        start, if its an array it is interpretted like
     *                        [int $from, int $count], $from indicates number of
     *                        records to skip and $count indicates number of records
     *                        to retrieve.
     *
     * @return array [rows]   containing the array of rows or empty array otherwise
     */
    public function get(string $table, array $options = []);

    /**
     * Retreives only the first record
     *
     * @param string $table   Table name
     * @param array  $options Options
     *
     * @return array  containing the associative key=>value pairs of the row
     *                or null otherwise
     */
    public function getFirst(string $table, array $options = []);

    /**
     * Verifies that the database has the record(s)
     *
     * @param string $table   Table name
     * @param array  $options Options
     *
     * @return bool
     */
    public function has(string $table, array $options = []): bool;
}
