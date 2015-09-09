<?php
namespace Vda\Datasource\Relational\Driver;

interface ISqlDialect
{
    public function quote($literal, $type);
    public function quoteIdentifier($identifier);
    public function patternMatchOperator($isCaseSensitive, $isNegative);
    public function quoteWildcards($literal);
    public function limitClause($limit, $offset = null);
}
