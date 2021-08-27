<?php

use IrfanTOOR\Test;
use IrfanTOOR\Database\Query;
use Tests\Users;

class QueryTest extends Test
{
    protected $q;

    # new
    function testInstance()
    {
        $q = new Query;
        $this->assertInstanceOf(Query::class, $q);
    }

    # constants
    function testDefaults()
    {
        $q = new Query;
        $this->assertEquals('0, 10', $q::DEFAULT_LIMIT);
        $this->assertEquals('1', $q::DEFAULT_DELETE_LIMIT);
    }

    function sampleDefaults()
    {
        return $defaults = [
            'raw'      => 'SELECT * from my_table;',
            'action'   => 'select', # can be select, insert, update, delete

            'select'   => 'id, date, name',
            'table'     => 'my_table',
            'joins'    => [],
            'where'    => 'id < :max',
            'orderby'  => 'id ASC',
            'limit'    => '9, 10',

            'record'   => [
                'id'   => 1,
                'name' => 'Mr. James',
            ],
            'bind'     => [
                'max' => 100,
            ],
        ];
    }

    function sampleRecord()
    {
        return [
            'id' => 1,
            'name' => 'Jhon',
            'time' => time(),
            'class' => Query::class,
            'role' => "rien n'a foutre!",
        ];
    }

    # init
    function testInit()
    {
        $q = new Query;
        $c = clone $q;
        $c->select('id')->from('users')->where('date > :date')->orderby('id');
        $this->assertNotEquals($q, $c);
        $this->assertEquals($q, $c->init());

        # check defaults
        $init = [
            'raw'      => null,
            'action'   => 'select', # can be select, insert, update, delete

            'select'   => '*',
            'table'     => 'TABLE_NAME',
            'joins'    => [],
            'where'    => null,
            'orderby'  => null,
            'limit'    => null,

            'record'   => [],
            'bind'     => [],
        ];

        $c = new Query($this->sampleDefaults());
        $q->init($this->sampleDefaults());
        $this->assertEquals($c, $q);

        $c
            ->init()
            ->raw('SELECT * from my_table;')
            ->select('id, date, name')
            ->from('my_table')
            ->where('id < :max')
            ->orderby('id ASC')
            ->limit('9, 10')
            ->record(['id' => 1, 'name' => 'Mr. James'])
            ->bind(['max' => 100])
        ;

        $this->assertEquals($c, $q);
        $this->assertEquals($c->init(), $q->init($init));
        $this->assertInstanceOf(Query::class, $c->init()); # for method chaining
    }

    # __call
    function testAliasCall()
    {
        $q = new Query;
        $c = clone $q;

        $this->assertEquals($c->from('Paris'), $q->table('Paris'));
        $this->assertEquals($c->from('California'), $q->into('California'));
        $this->assertEquals($c->from('Lisbon'), $q->in('Lisbon'));

        $this->assertInstanceOf(Query::class, $c->table('')); # for method chaining
    }

    /**
     * __call
     * throws: Exception::class
     * message: Unknow method: hello
     */
    function testUnknownMethodCall()
    {
        $q = new Query;
        $q->hello();
    }

    # get
    function testGet()
    {
        $q = new Query();
        $q->from('my_table');
        $this->assertEquals('my_table', $q->get('from'));
        $this->assertEquals('my_table', $q->get('table'));
        $this->assertEquals('my_table', $q->get('into'));
        $this->assertEquals('my_table', $q->get('in'));

        $q->select('id')->limit('100')->where('1=1')->orderby('id DESC');
        $this->assertEquals('id', $q->get('select'));
        $this->assertEquals('100', $q->get('limit'));
        $this->assertEquals('1=1', $q->get('where'));
        $this->assertEquals([], $q->get('join'));
        $this->assertEquals('id DESC', $q->get('orderby'));

        $q->record([
            'id' => 1,
            'hello' => 'world'
        ]);
        $this->assertEquals(['id' => 1, 'hello' => 'world'], $q->get('record'));

        $q->raw('SELECT me');
        $this->assertEquals('SELECT me', $q->get('raw'));

        $q->raw('SELECT me');
        $this->assertEquals('SELECT me', $q->get('raw'));

        $q->bind(['id' => 9, 'max' => 100]);
        $this->assertEquals(['id' => 9, 'max' => 100], $q->get('bind'));
    }

    # options
    function testOptions()
    {
        $q = new Query;

        $defaults = $this->sampleDefaults();
        unset($defaults['table']);

        $q->from('some_table')->options($defaults);
        $this->assertEquals('some_table', $q->get('from'));
        foreach ($defaults as $k => $v)
            $this->assertEquals($v, $q->get($k));

        $this->assertInstanceOf(
            Query::class, $q->options(['raw' => 'SELECT OPTIONS'])
        ); # for method chaining
    }

    # raw
    function testRaw()
    {
        $q = new Query;
        $q->raw('SELECT HELLO FROM WORLD');
        $this->assertEquals('SELECT HELLO FROM WORLD', $q->get('raw'));
        $this->assertEquals('SELECT HELLO FROM WORLD', (string) $q);

        $this->assertInstanceOf(
            Query::class, $q->raw('')
        ); # for method chaining
    }

    # select
    public function testSelect()
    {
        $q = new Query;
        $this->assertZero(strpos($q, 'SELECT * FROM'));
        $this->assertNotFalse(strpos($q, 'LIMIT'));

        $q->from('table');
        $this->assertEquals('*', $q->get('select'));
        $this->assertZero(strpos($q, 'SELECT * FROM'));
        $this->assertNotFalse(strpos($q, 'LIMIT'));

        $q->select()->from('table');
        $this->assertEquals('*', $q->get('select'));
        $this->assertZero(strpos($q, 'SELECT * FROM table'));
        $this->assertNotFalse(strpos($q, 'LIMIT'));

        $q->select('id, name, date')->from('table');
        $this->assertEquals('id, name, date', $q->get('select'));
        $this->assertZero(strpos($q, 'SELECT id, name, date FROM table'));
        $this->assertNotFalse(strpos($q, 'LIMIT'));

        $this->assertInstanceOf(
            Query::class, $q->select()
        ); # for method chaining
    }

    # from alias: table, into, in
    public function testFrom()
    {
        $q = new Query;
        $this->assertZero(strpos($q, 'SELECT * FROM TABLE_NAME'));

        $q->from('from_table');
        $this->assertEquals('from_table', $q->get('from'));
        $this->assertZero(strpos($q, 'SELECT * FROM from_table'));

        $q->table('table_table');
        $this->assertEquals('table_table', $q->get('from'));

        $q->into('into_table');
        $this->assertEquals('into_table', $q->get('from'));

        $q->in('in_table');
        $this->assertEquals('in_table', $q->get('from'));

        $this->assertInstanceOf(
            Query::class, $q->from('another_universe')
        ); # for method chaining
    }

    # join
    public function testJoin()
    {
        # todo --
    }

    # where
    public function testWhere()
    {
        $q = new Query;
        $q->where('hello=world');
        $this->assertEquals('hello=world', $q->get('where'));
        $this->assertNotFalse(strpos($q, 'WHERE hello=world'));

        $q->where('love=life');
        $this->assertEquals('(hello=world AND love=life)', $q->get('where'));
        $this->assertNotFalse(strpos($q, 'WHERE (hello=world AND love=life)'));

        $q->where('loop=infinite', 'OR');
        $this->assertEquals('((hello=world AND love=life) OR loop=infinite)', $q->get('where'));
        $this->assertNotFalse(strpos($q, '((hello=world AND love=life) OR loop=infinite)'));

        $this->assertInstanceOf(
            Query::class, $q->where('quantum="unpredictable OR indecisive bit away!"')
        ); # for method chaining
    }

    # order
    public function testOrderby()
    {
        $q = new Query;
        $this->assertFalse(strpos($q, 'ORDER BY'));
        $q->orderby('id');
        $this->assertEquals("id", $q->get('orderby'));
        $this->assertNotFalse(strpos($q, 'ORDER BY id'));

        $q->orderby('date ASC');
        $this->assertEquals("id, date ASC", $q->get('orderby'));
        $this->assertNotFalse(strpos($q, 'ORDER BY id, date ASC'));

        $this->assertInstanceOf(
            Query::class, $q->orderby('new world')
        ); # for method chaining
    }

    # limit
    public function testLimit()
    {
        $q = new Query;
        $this->assertNotFalse(strpos($q, 'LIMIT'));
        $q->from('paradise');
        $this->assertNull($q->get('limit'));
        $sql = (string) $q;
        preg_match('|LIMIT (.*);|', $sql, $m);
        $this->assertEquals($q::DEFAULT_LIMIT, $m[1]);

        $q->delete()->from('paradise');
        $this->assertNull($q->get('limit'));
        $this->assertNull($q->get('where'));

        $sql = (string) $q;
        preg_match('|LIMIT (.*);|', $sql, $m);
        $this->assertEquals([], $m);

        preg_match('|WHERE (.*);|', $sql, $m);
        $this->assertEquals('0=1', $m[1]);

        $q->delete()->from('paradise')->where('humans like "apple"');
        $this->assertEquals('humans like "apple"', $q->get('where')); # earth
        $this->assertFalse(strpos($q, 'LIMIT'));

        $q->limit('666');
        $this->assertEquals('666', $q->get('limit'));
        $this->assertFalse(strpos($q, 'LIMIT'));
        $q->init()->limit('666');
        $this->assertEquals('666', $q->get('limit'));
        $this->assertNotFalse(strpos($q, 'LIMIT'));

        $q->limit('0, 1');
        $this->assertEquals('0, 1', $q->get('limit'));
        $this->assertNotFalse(strpos($q, 'LIMIT'));

        $this->assertInstanceOf(
            Query::class, $q->limit('sans limit')
        ); # for method chaining
    }

    # record
    public function testRecord()
    {
        $q = new Query;
        $r = $this->SampleRecord();

        $q->record($r);
        $this->assertEquals($r, $q->get('record'));

        $this->assertInstanceOf(
            Query::class, $q->record([])
        ); # for method chaining
    }

    #bind
    function testBind()
    {
        $q = new Query;
        $r = $this->SampleRecord();
        $q->bind($r);
        $this->assertEquals($r, $q->get('bind'));

        # adds as another element
        $q->bind(['yin' => 'yang']);
        $r = array_merge($r, ['yin' => 'yang']);
        $this->assertEquals($r, $q->get('bind'));

        $this->assertInstanceOf(
            Query::class, $q->record([])
        ); # for method chaining
    }

    # insert
    function testInsert()
    {
        $q = new Query;
        $r = $this->SampleRecord();

        $q->insert($r);
        $this->assertEquals($r, $q->get('record'));
        $this->assertZero(strpos($q, 'INSERT INTO'));

        $this->assertInstanceOf(
            Query::class, $q->insert([])
        ); # for method chaining
    }

    # update
    function testUpdate()
    {
        $q = new Query;
        $r = $this->SampleRecord();

        $q->update($r);
        $this->assertEquals($r, $q->get('record'));
        $this->assertZero(strpos($q, 'UPDATE TABLE_NAME'));

        $this->assertInstanceOf(
            Query::class, $q->update([])
        ); # for method chaining
    }

    # insertOrUpdate
    function testInsertOrUpdate()
    {
        $q = new Query;
        $r = $this->SampleRecord();

        $q->insertOrUpdate($r);
        $this->assertEquals($r, $q->get('record'));
        $this->assertZero(strpos($q, 'INSERT OR REPLACE INTO TABLE_NAME'));

        $this->assertInstanceOf(
            Query::class, $q->insertOrUpdate([])
        ); # for method chaining
    }

    # insertOrUpdate
    function testDelete()
    {
        $q = new Query;
        $q->delete('the_track');
        $this->assertZero(strpos($q, 'DELETE FROM the_track'));

        $q->init()->delete()->from('the_track');
        $this->assertZero(strpos($q, 'DELETE FROM the_track'));

        $this->assertInstanceOf(
            Query::class, $q->delete()
        ); # for method chaining
    }

    function testToString()
    {
        $q = new Query;
        $this->assertTrue(method_exists($q, '__toString'));
        $this->assertEquals(
            'SELECT * FROM TABLE_NAME LIMIT 0, 10;', $q->__toString()
        );

        $q
            ->select('id, name, age, level')
            ->from('the_people')
            ->where('level=:dumb')
            ->orderby('age DESC')
            ->limit('10')
            ->bind(['dumb' => 0])
        ;

        $this->assertEquals(
            'SELECT id, name, age, level FROM the_people WHERE' .
            ' level=:dumb ORDER BY age DESC LIMIT 10;',
            $q->__toString()
        );

        $q->init()->raw('BINGO');
        $this->assertEquals('BINGO', (string) $q);
    }
}
