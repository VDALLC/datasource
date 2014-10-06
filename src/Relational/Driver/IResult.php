<?php
namespace Vda\Datasource\Relational\Driver;

interface IResult
{
    public function numRows();
    public function fetch();
    public function fetchTuple();
    public function fetchVal($offset = 0);
    public function fetchAll();
}
