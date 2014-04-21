<?php
namespace Vda\Datasource\Relational;

use Psr\Log\LoggerInterface;
use Vda\Datasource\IRepository;
use Vda\Datasource\Relational\Driver\IConnection;
use Vda\Query\Select;
use Vda\Query\Insert;
use Vda\Query\Update;
use Vda\Query\Delete;

class Repository implements IRepository
{
    private $conn;
    private $qb;
    private $logger;

    public function __construct(IConnection $conn, LoggerInterface $logger = null)
    {
        $this->conn = $conn;
        $this->logger = $logger;
        $this->qb = new QueryBuilder($conn->getDialect());
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

    /**
     * @param Insert $insert
     * @return integer Number of affected rows
     */
    public function insert(Insert $insert)
    {
        $q = $this->qb->build($insert);
        if ($this->logger) {
            $this->logger->debug($q);
        }
        return $this->conn->exec($q);
    }

    /**
     * @param Update $update
     * @return integer Number of affected rows
    */
    public function update(Update $update)
    {
        $q = $this->qb->build($update);
        if ($this->logger) {
            $this->logger->debug($q);
        }
        return $this->conn->exec($q);
    }

    /**
     * @param Delete $delete
     * @return integer Number of affected rows
    */
    public function delete(Delete $delete)
    {
        $q = $this->qb->build($delete);
        if ($this->logger) {
            $this->logger->debug($q);
        }
        return $this->conn->exec($q);
    }

    /**
     * @return integer Value of autoincrement field if available
    */
    public function getLastInsertId()
    {
        return $this->conn->lastInsertId();
    }
}
