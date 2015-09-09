<?php

use Vda\Datasource\Relational\Driver\Mysqli\MysqlConnection;
use Vda\Datasource\Relational\Repository;

class RepositoryTestClass extends PHPUnit_Framework_TestCase
{
    /**
     * @var Repository
     */
    private $repository;

    public function setUp()
    {
        $this->repository = new Repository(new MysqlConnection('mysql://root@localhost/test'));
    }

    public function testTransaction()
    {
        $this->assertFalse($this->repository->isTransactionStarted());
        $res = $this->repository->transaction(function() {
            $this->assertTrue($this->repository->isTransactionStarted());
            return 25;
        });
        $this->assertEquals(25, $res);
        $this->assertFalse($this->repository->isTransactionStarted());
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage qwe
     */
    public function testTransactionException()
    {
        $this->repository->transaction(function() {
            throw new Exception('qwe');
        });
    }
}
