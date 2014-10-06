<?php
namespace Vda\Datasource;

use Vda\Transaction\ITransactionCapable;

interface ITransactionCapableRepository extends IRepository, ITransactionCapable
{
}
