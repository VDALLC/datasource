<?php
namespace Vda\Datasource\Relational\Driver\Mysql;

use Vda\Datasource\DatasourceException;
use Vda\Datasource\Relational\Driver\IConnection;

class MysqlConnection implements IConnection
{
    private $dsn;
    private $conn;

    /**
     * Connect to database described by $dsn
     *
     * $dsn must be either boolean false to prevent connection
     * or string containing DSN
     *
     * @param mixed $dsn
     * @see IConnection::connect() for DSN format
     * @throws DatasourceException
     */
    public function __construct($dsn)
    {
        if ($dsn !== false) {
            $this->connect($dsn);
        }
    }

    public function connect($dsn, $closePrevious = false)
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

        $this->dsn = $dsn;

        $p = $this->parseDsn($dsn);

        if ($p['persistent']) {
            $this->conn = mysql_pconnect($p['host'], $p['user'], $p['pass']);
        } else {
            $this->conn = mysql_connect($p['host'], $p['user'], $p['pass'], true);
        }

        if (empty($this->conn)) {
            throw new DatasourceException(
                'DB connection failed: ' . mysql_error()
            );
        }

        if (!mysql_select_db($p['db'], $this->conn)) {
            // todo mysql_error($this->conn) not working after $this->disconnect();
            $this->disconnect();
            throw new DatasourceException(
                'DB connection failed: ' . mysql_error($this->conn)
            );
        }

        if (!mysql_set_charset($p['charset'], $this->conn)) {
            $this->disconnect();
            throw new DatasourceException(
                'Unable to set character set: ' . mysql_error($this->conn)
            );
        }
    }

    public function disconnect()
    {
        if ($this->isConnected()) {
            mysql_close($this->conn);
            $this->conn = null;
        }
    }

    public function isConnected()
    {
        return !empty($this->conn);
    }

    public function query($q)
    {
        $rs = mysql_query($q, $this->conn);

        if (empty($rs)) {
            throw new DatasourceException(mysql_error($this->conn));
        }

        return is_resource($rs) ? new MysqlResult($rs) : null;
    }

    public function exec($q)
    {
        if (!mysql_query($q, $this->conn)) {
            throw new DatasourceException(mysql_error($this->conn));
        }

        return $this->affectedRows();
    }

    public function affectedRows()
    {
        return mysql_affected_rows($this->conn);
    }

    public function lastInsertId()
    {
        return mysql_insert_id($this->conn);
    }

    public function getDialect()
    {
        return new MysqlDialect($this->conn);
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
        );

        $persistent = false;
        $charset = 'utf8';

        $result['host'] = $params['host'];
        if (!empty($params['port'])) {
            $result['host'] .= ':' . $params['port'];
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
}
