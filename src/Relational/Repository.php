<?php
namespace Vda\Datasource\Relational;

use Vda\Datasource\ISavepointCapableRepository;
use Vda\Datasource\Relational\Driver\IConnection;
use Vda\Query\Delete;
use Vda\Query\Field;
use Vda\Query\Insert;
use Vda\Query\Select;
use Vda\Query\Update;
use Vda\Query\Upsert;
use Vda\Transaction\DecoratingTransactionListener;
use Vda\Transaction\ITransactionListener;
use Vda\Util\Type;

class Repository implements ISavepointCapableRepository
{
    private $conn;
    private $qb;

    public function __construct(IConnection $conn)
    {
        $this->conn = $conn;
        $this->qb = $conn->getQueryBuilder();
        $this->listener = new DecoratingTransactionListener($this);
        $this->conn->addTransactionListener($this->listener);
    }

    public function select(Select $select)
    {
        $accumulator = $select->getResultAccumulator();
        $accumulator->reset($select->getProjection());

        $q = $this->qb->build($select);
        $rs = $this->conn->query($q);

        while ($tuple = $rs->fetchTuple()) {
            $accumulator->accumulate($this->unmarshall($select->getFields(), $tuple));

            if ($accumulator->isFilled()) {
                break;
            }
        }

        return $accumulator->getResult();
    }

    public function insert(Insert $insert)
    {
        $q = $this->qb->build($insert);

        return $this->conn->exec($q);
    }

    public function upsert(Upsert $upsert)
    {
        $q = $this->qb->build($upsert);

        return $this->conn->exec($q);
    }

    public function update(Update $update)
    {
        $q = $this->qb->build($update);

        return $this->conn->exec($q);
    }

    public function delete(Delete $delete)
    {
        $q = $this->qb->build($delete);

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

    public function transaction($callback)
    {
        return $this->conn->transaction($callback);
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

    protected function unmarshall(array $fields, array $tuple)
    {
        $num = count($fields);

        for ($i = 0; $i < $num; $i++) {
            if (
                $fields[$i] instanceof Field &&
                $fields[$i]->getType() == Type::DATE &&
                !is_null($tuple[$i])
            ) {
                $tuple[$i] = new \DateTime($tuple[$i]);
            }
        }

        return $tuple;
    }
}
