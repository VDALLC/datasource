<?php
namespace Vda\Datasource\Relational\Driver\Mysqli;

use Vda\Datasource\DatasourceException;
use Vda\Datasource\Relational\Driver\IConnection;
use Vda\Transaction\CompositeTransactionListener;
use Vda\Transaction\ITransactionListener;
use Vda\Transaction\TransactionException;

class MysqlConnection implements IConnection
{
    private $parsedDsn;

    /**
     * @var \mysqli
     */
    private $mysql;

    private $isTransactionStarted;
    private $listeners;
    private $builderStateFactory;

    /**
     * Connect to database described by $dsn
     *
     * $dsn must be either boolean false to prevent connection
     * or string containing DSN
     *
     * @param string $dsn
     * @param bool $autoConnect
     * @see IConnection::connect() for DSN format
     */
    public function __construct($dsn, $autoConnect = true)
    {
        $this->parsedDsn = $this->parseDsn($dsn);
        $this->isTransactionStarted = false;
        $this->listeners = new CompositeTransactionListener();

        if ($autoConnect) {
            $this->connect();
        }

        $this->builderStateFactory = new MysqlQueryBuilderStateFactory();
    }

    public function connect($closePrevious = false)
    {
        if ($this->isConnected()) {
            if ($closePrevious) {
                $this->disconnect();
            } else {
                throw new DatasourceException(
                    'Already connected. Close current connection first'
                );
            }
        }

        $this->mysql = new \mysqli(
            $this->parsedDsn['persistent'] ? 'p:' . $this->parsedDsn['host'] : $this->parsedDsn['host'],
            $this->parsedDsn['user'],
            $this->parsedDsn['pass'],
            $this->parsedDsn['db'],
            $this->parsedDsn['port'],
            $this->parsedDsn['socket']
        );

        if ($this->mysql->connect_errno) {
            throw new DatasourceException(
                'DB connection failed: ' . $this->mysql->connect_error
            );
        }

        if (!$this->mysql->set_charset($this->parsedDsn['charset'])) {
            $this->disconnect();
            throw new DatasourceException(
                'Unable to set character set: ' . $this->mysql->error
            );
        }
    }

    public function disconnect()
    {
        if ($this->isConnected()) {
            $this->mysql->close();
            $this->mysql = null;
        }

        if ($this->isTransactionStarted) {
            $this->isTransactionStarted = false;
            $this->listeners->onTransactionRollback($this);
        }
    }

    public function isConnected()
    {
        return !empty($this->mysql) && $this->mysql->ping();
    }

    public function query($q)
    {
        $rs = $this->mysql->query($q);

        if (!$rs) {
            throw new DatasourceException($this->mysql->error);
        }

        return is_object($rs) ? new MysqlResult($rs) : null;
    }

    public function exec($q)
    {
        if (!$this->mysql->query($q)) {
            throw new DatasourceException($this->mysql->error);
        }

        return $this->affectedRows();
    }

    public function affectedRows()
    {
        return $this->mysql->affected_rows;
    }

    public function lastInsertId()
    {
        return $this->mysql->insert_id;
    }

    public function getDialect()
    {
        return new MysqlDialect($this);
    }

    public function getQueryBuilder()
    {
        return new MysqlQueryBuilder($this->getDialect(), $this->builderStateFactory);
    }

    public function begin()
    {
        if ($this->isTransactionStarted) {
            throw new TransactionException('Transaction is already started');
        }

        if (!$this->mysql->autocommit(false)) {
            throw new TransactionException('Unable to start transaction');
        }

        $this->isTransactionStarted = true;

        $this->listeners->onTransactionBegin($this);
    }

    public function commit()
    {
        if (!$this->isTransactionStarted) {
            throw new TransactionException('Commit failed, transaction is not started');
        }

        if (!$this->mysql->commit()) {
            throw new TransactionException('Unable to commit transaction');
        }

        $this->isTransactionStarted = false;
        $this->mysql->autocommit(true);

        $this->listeners->onTransactionCommit($this);
    }

    public function rollback()
    {
        if (!$this->isTransactionStarted) {
            throw new TransactionException('Rollback failed, transaction is not started');
        }

        if (!$this->mysql->rollback()) {
            throw new TransactionException('Unable to rollback transaction');
        }

        $this->isTransactionStarted = false;
        $this->mysql->autocommit(true);

        $this->listeners->onTransactionRollback($this);
    }

    public function savepoint($savepoint)
    {
        if (!$this->isTransactionStarted) {
            throw new TransactionException('Savepoint creation failed, transaction is not started');
        }

        try {
            $this->query('SAVEPOINT ' . $this->getDialect()->quoteIdentifier($savepoint));
            $this->listeners->onSavepointCreate($this, $savepoint);
        } catch (DatasourceException $e) {
            throw new TransactionException('Unable to create the savepoint', 0, $e);
        }
    }

    public function release($savepoint)
    {
        if (!$this->isTransactionStarted) {
            throw new TransactionException('Savepoint release failed, transaction is not started');
        }

        try {
            $this->query('RELEASE SAVEPOINT ' . $this->getDialect()->quoteIdentifier($savepoint));
            $this->listeners->onSavepointRelease($this, $savepoint);
        } catch (DatasourceException $e) {
            throw new TransactionException('Unable to release savepoint', 0, $e);
        }
    }

    public function rollbackTo($savepoint)
    {
        if (!$this->isTransactionStarted) {
            throw new TransactionException('Rollback to savepoint failed, transaction is not started');
        }

        try {
            $this->query('ROLLBACK TO ' . $this->getDialect()->quoteIdentifier($savepoint));
            $this->listeners->onSavepointRollback($this, $savepoint);
        } catch (DatasourceException $e) {
            throw new TransactionException('Unable to rollback to savepoint', 0, $e);
        }
    }

    public function isTransactionStarted()
    {
        return $this->isTransactionStarted;
    }

    public function addTransactionListener(ITransactionListener $listener)
    {
        $this->listeners->addListener($listener);
    }

    public function removeTransactionListener(ITransactionListener $listener)
    {
        $this->listeners->removeListener($listener);
    }

    private function parseDsn($dsn)
    {
        $params = parse_url($dsn);

        if ($params === false) {
            throw new DatasourceException('Unable to parse DSN');
        }

        if (empty($params['scheme']) || $params['scheme'] != 'mysql') {
            throw new DatasourceException(
                "Invalid DSN scheme. Expected: 'mysql', given: '{$params['scheme']}'"
            );
        }

        $result = array(
            'host'       => 'localhost',
            'user'       => 'root',
            'pass'       => '',
            'db'         => null,
            'charset'    => 'UTF8',
            'persistent' => false,
            'port'       => null,
            'socket'     => null,
        );

        $result['host'] = $params['host'];

        if (!empty($params['port'])) {
            $result['port'] = $params['port'];
        }

        if (isset($params['user'])) {
            $result['user'] = $params['user'];
        }

        if (isset($params['pass'])) {
            $result['pass'] = $params['pass'];
        }

        if (isset($params['path'])) {
            $result['db'] = ltrim($params['path'], '/');
        }

        if (!empty($params['query'])) {
            parse_str($params['query'], $opt);

            $result['persistent'] = !empty($opt['persistent']);

            if (!empty($opt['charset'])) {
                $result['charset'] = $opt['charset'];
            }
        }

        return $result;
    }

    public function escapeString($str)
    {
        if ($this->mysql) {
            return $this->mysql->real_escape_string($str);
        } else {
            throw new DatasourceException('No database connection');
        }
    }

    public function setPersistable($flag)
    {
        $this->parsedDsn['persistent'] = (bool)$flag;
    }
}
