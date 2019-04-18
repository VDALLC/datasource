<?php
namespace Vda\Datasource\Relational\Driver\Mysqli;

use Vda\Datasource\Relational\Driver\IResult;

class MysqlResult implements IResult
{
    private $rs;

    public function __construct(\mysqli_result $rs)
    {
        $this->rs = $rs;
    }

    public function numRows()
    {
        return $this->rs->num_rows;
    }

    public function fetch()
    {
        return $this->rs->fetch_assoc();
    }

    public function fetchTuple()
    {
        return $this->rs->fetch_row();
    }

    public function fetchVal($offset = 0)
    {
        $row = $this->rs->fetch_row();

        return empty($row) ? null : $row[$offset];
    }

    public function fetchAll()
    {
        $result = [];

        while ($row = $this->rs->fetch_assoc()) {
            $result[] = $row;
        }

        return $result;
    }
}
