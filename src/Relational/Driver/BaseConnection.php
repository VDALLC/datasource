<?php
namespace Vda\Datasource\Relational\Driver;

use Psr\Log\LoggerInterface;

abstract class BaseConnection implements IConnection
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Execute the SQL query
     *
     * @param string $q Query to execute
     * @return IResult
     */
    abstract protected function doQuery($q);

    /**
     * @param string $query
     * @return mixed $function result
     */
    protected function queryAndProfile($query)
    {
        $start = microtime(true);

        $result = $this->doQuery($query);

        if ($this->logger) {
            $duration = number_format(microtime(true) - $start, 5, '.', ' ');
            $this->logger->debug("{$query} run {$duration} s");
        }

        return $result;
    }
}
