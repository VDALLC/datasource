<?php
namespace Vda\Datasource\Relational\Driver;

use Vda\Datasource\DatasourceException;
use Vda\Transaction\ISavepointCapable;

/**
 * It is suggested to use DSN in interface implementers __construct(). DSN must be a string in following format:
 * <pre>
 * driver://[user[:pass]]@host[:port]/db[?[charset=<charset>][&persistent=<0|1>]]
 * </pre>
 * @param string $dsn
 * @throws DatasourceException
 */
interface IConnection extends ISavepointCapable
{
    public function connect();

    public function disconnect();

    public function isConnected();

    /**
     * Set connection type for next connect()
     *
     * @param bool $flag
     */
    public function setPersistable($flag);

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

    public function escapeString($str);
}
