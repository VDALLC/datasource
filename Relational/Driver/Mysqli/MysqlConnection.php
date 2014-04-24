<?php
namespace Vda\Datasource\Relational\Driver\Mysqli;

use Vda\Datasource\DatasourceException;
use Vda\Datasource\Relational\Driver\IConnection;

class MysqlConnection implements IConnection
{
    private $dsn;
    private $mysql;

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

        $this->mysql = new \mysqli(
            $p['persistent'] ? 'p:' . $p['host'] : $p['host'],
            $p['user'],
            $p['pass'],
            $p['db'],
            $p['port'],
            $p['socket']
        );

        if ($this->mysql->connect_errno) {
            throw new DatasourceException(
                'DB connection failed: ' . $this->mysql->connect_error
            );
        }

        if (!$this->mysql->set_charset($p['charset'])) {
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
        return new MysqlDialect($this->mysql);
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
