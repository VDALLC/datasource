<?php
namespace Vda\Datasource\Relational\Driver\Mysqli;

use Vda\Datasource\Relational\QueryBuilderState;
use Vda\Datasource\Relational\QueryBuilderStateFactory;

class MysqlQueryBuilderStateFactory extends QueryBuilderStateFactory
{
    public function upsert()
    {
        if (empty($this->upsertState)) {
            $this->upsertState = new QueryBuilderState();
            $this->upsertState->setScopeIgnored(true);
            $this->upsertState->setAliasIgnored(true);
            $this->upsertState->setSelectParenthesized(false);
        }

        return $this->upsertState;
    }
}
