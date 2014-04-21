<?php
namespace Vda\Datasource;

use Vda\Query\Select;

interface IDatasource
{
    public function select(Select $select);
}
