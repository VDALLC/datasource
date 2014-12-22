<?php
namespace Vda\Datasource\Relational;

use Vda\Query\IQueryPart;

interface IQueryBuilder
{
    /**
     * Render a query string from given query object
     *
     * @param IQueryPart $query
     * @return string
     */
    public function build(IQueryPart $query);
}
