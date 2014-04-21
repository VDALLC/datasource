<?php
namespace Vda\Datasource\Relational\Driver\Mysql;

use Vda\Datasource\Relational\Driver\IResult;

class MysqlResult implements IResult
{
    private $rs;

    public function __construct($rs)
    {
        $this->rs = $rs;
    }

    public function numRows()
    {
        return mysql_num_rows($this->rs);
    }

    public function fetch()
    {
        return mysql_fetch_assoc($this->rs);
    }

    public function fetchTuple()
    {
        return mysql_fetch_array($this->rs);
    }

    public function fetchVal($offset = 0)
    {
        $row = mysql_fetch_array($this->rs);

        return empty($row) ? null : $row[$offset];
    }

    public function fetchAll()
    {
        $result = array();

        while ($row = mysql_fetch_assoc($this->rs)) {
            $result[] = $row;
        }

        return $result;
    }
}
