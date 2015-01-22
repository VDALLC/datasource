<?php
namespace Vda\Datasource\Relational\Driver\Mysqli;

use Vda\Datasource\Relational\Driver\IConnection;
use Vda\Datasource\Relational\Driver\ISqlDialect;
use Vda\Util\Type;

class MysqlDialect implements ISqlDialect
{
    private $conn;

    public function __construct(IConnection $conn = null)
    {
        $this->conn = $conn;
    }

    public function quote($literal, $type)
    {
        if (is_null($literal)) {
            return 'null';
        }

        if ($type == Type::BOOLEAN) {
            return $literal ? '1' : '0';
        }

        if (($type & Type::NUMERIC) > 0) {
            if (!is_numeric($literal)) {
                throw new \InvalidArgumentException(
                    "The '{$literal}' literal must be numeric"
                );
            }

            return $literal;
        }

        if ($type == Type::DATE) {
            if ($literal instanceof \DateTimeInterface) {
                return $literal->format("'Y-m-d H:i:s'");
            } else {
                $timestamp = strtotime($literal);

                if ($timestamp === false) {
                    throw new \InvalidArgumentException(
                        "The '{$literal}' literal must be valid date"
                    );
                }

                return date("'Y-m-d H:i:s'", $timestamp);
            }
        }

        if ($type == Type::STRING) {
            return $this->escapeString($literal, "'");
        }

        throw new \InvalidArgumentException("The '{$type}' type is unknown");
    }

    public function quoteIdentifier($identifier)
    {
        $scope = '';
        $period = strpos($identifier, '.');
        if ($period !== false) {
            $scope = $this->escapeString(substr($identifier, 0, $period), '`') . '.';
            $identifier = substr($identifier, $period + 1);
        }

        return $scope . $this->escapeString($identifier, '`');
    }

    public function patternMatchOperator($isCaseSensitive, $isNegative)
    {
        $modifier = $isCaseSensitive ? ' BINARY ' : ' ';
        $negator = $isNegative ? ' NOT ' : ' ';

        return $negator . 'LIKE' . $modifier;
    }

    public function limitClause($limit, $offset = null)
    {
        if (!is_numeric($limit)) {
            throw new \InvalidArgumentException('Limit value must be numeric');
        }

        if (!is_null($offset) && !is_numeric($offset)) {
            throw new \InvalidArgumentException('Offset value must be numeric');
        }

        if (is_null($offset)) {
            $result = 'LIMIT ' . $limit;
        } else {
            $result = 'LIMIT ' . $offset . ', ' . $limit;
        }

        return $result;
    }

    protected function escapeString($str, $encl)
    {
        return $encl . $this->conn->escapeString($str) . $encl;
    }
}
