<?php
namespace Vda\Datasource\Relational;

use Psr\Log\LoggerInterface;
use Vda\Datasource\ISavepointCapableRepository;
use Vda\Datasource\Relational\Driver\IConnection;
use Vda\Query\Select;
use Vda\Query\Insert;
use Vda\Query\Update;
use Vda\Query\Delete;
use Vda\Transaction\DecoratingTransactionListener;
use Vda\Transaction\ITransactionListener;

class Repository implements ISavepointCapableRepository
{
    private $conn;
    private $qb;
    private $logger;

    public function __construct(IConnection $conn, LoggerInterface $logger = null)
    {
        $this->conn = $conn;
        $this->logger = $logger;
        $this->qb = new QueryBuilder($conn->getDialect());
        $this->listener = new DecoratingTransactionListener($this);
        $this->conn->addTransactionListener($this->listener);
    }

    public function select(Select $select)
    {
        $accumulator = $select->getResultAccumulator();
        $accumulator->reset($select->getProjection());

        $q = $this->qb->build($select);
        if ($this->logger) {
            $this->logger->debug($q);
        }
        $rs = $this->conn->query($q);

        while ($tuple = $rs->fetchTuple()) {
            $accumulator->accumulate($tuple);

            if ($accumulator->isFilled()) {
                break;
            }
        }

        return $accumulator->getResult();
    }

    public function insert(Insert $insert)
    {
        $q = $this->qb->build($insert);
        if ($this->logger) {
            $this->logger->debug($q);
        }
        return $this->conn->exec($q);
    }

    public function update(Update $update)
    {
        $q = $this->qb->build($update);
        if ($this->logger) {
            $this->logger->debug($q);
        }
        return $this->conn->exec($q);
    }

    public function delete(Delete $delete)
    {
        $q = $this->qb->build($delete);
        if ($this->logger) {
            $this->logger->debug($q);
        }
        return $this->conn->exec($q);
    }

    public function getLastInsertId()
    {
        return $this->conn->lastInsertId();
    }

    public function begin()
    {
        $this->conn->begin();
    }

    public function savepoint($savepoint)
    {
        $this->conn->savepoint($savepoint);
    }

    public function release($savepoint)
    {
        $this->conn->release($savepoint);
    }

    public function rollbackTo($savepoint)
    {
        $this->conn->rollbackTo($savepoint);
    }

    public function commit()
    {
        $this->conn->commit();
    }

    public function rollback()
    {
        $this->conn->rollback();
    }

    public function isTransactionStarted()
    {
        return $this->conn->isTransactionStarted();
    }

    public function addTransactionListener(ITransactionListener $listener)
    {
        $this->listener->addListener($listener);
    }

    public function removeTransactionListener(ITransactionListener $listener)
    {
        $this->listener->removeListener($listener);
    }
}
