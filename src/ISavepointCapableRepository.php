<?php
namespace Vda\Datasource;

use Vda\Transaction\ISavepointCapable;

interface ISavepointCapableRepository extends ITransactionCapableRepository, ISavepointCapable
{
}
