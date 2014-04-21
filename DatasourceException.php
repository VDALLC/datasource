<?php
namespace Vda\Datasource;

class DatasourceException extends \RuntimeException
{
    public function __construct($msg, \Exception $cause = null)
    {
        parent::__construct($msg, 500, $cause);
    }
}
