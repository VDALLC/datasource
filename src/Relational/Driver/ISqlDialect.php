<?php
namespace Vda\Datasource\Relational\Driver;

interface ISqlDialect
{
    public function quote($literal, int $type);
    public function quoteIdentifier(string $identifier);
    public function patternMatchOperator(bool $isCaseSensitive, bool $isNegative);
    public function jsonGetOperator(string $path);
    public function quoteWildcards(string $literal);
    public function limitClause(int $limit, int $offset = null);
}
