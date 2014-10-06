<?php
namespace Vda\Datasource\Relational\Driver;

use Vda\Datasource\DatasourceException;
use Vda\Transaction\ISavepointCapable;

interface IConnection extends ISavepointCapable
{
    /**
     * Establish connection to the server described by $dsn
     *
     * DSN must be a string in following format:
     * <pre>
     * driver://[user[:pass]]@host[:port]/db[?[charset=<charset>][&persistent=<0|1>]]
     * </pre>
     * @param string $dsn
     * @throws DatasourceException
     */
    public function connect($dsn);

    public function disconnect();

    public function isConnected();

    /**
     * Execute the SQL query
     *
     * @param string $q Query to execute
     * @return IResult
     */
    public function query($q);

    /**
     * Execute the SQL query and return number of affected rows
     *
     * @param string $q
     * @return integer
     */
    public function exec($q);

    public function lastInsertId();

    public function affectedRows();

    /**
     * Return SQL dialect for this driver
     *
     * @return ISqlDialect
     */
    public function getDialect();
}
