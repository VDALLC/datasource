<?php
namespace Vda\Datasource\Relational;

class QueryBuilderStateFactory
{
    protected $rootState;
    protected $selectState;
    protected $insertState;
    protected $updateState;
    protected $upsertState;
    protected $deleteState;
    protected $groupOrderState;
    protected $matchOperatorState;

    public function __construct()
    {
    }

    /**
     * @return QueryBuilderState
     */
    public function root()
    {
        if (empty($this->rootState)) {
            $this->rootState = new QueryBuilderState();
            $this->rootState->setRoot(true);
        }

        return $this->rootState;
    }

    /**
     * @return QueryBuilderState
     */
    public function select()
    {
        if (empty($this->selectState)) {
            $this->selectState = new QueryBuilderState();
        }

        return $this->selectState;
    }

    /**
     * @return QueryBuilderState
     */
    public function insert()
    {
        if (empty($this->insertState)) {
            $this->insertState = new QueryBuilderState();
            $this->insertState->setScopeIgnored(true);
            $this->insertState->setAliasIgnored(true);
            $this->insertState->setSelectParenthesized(false);
        }

        return $this->insertState;
    }

    /**
     * @return QueryBuilderState
     */
    public function update()
    {
        if (empty($this->updateState)) {
            $this->updateState = new QueryBuilderState();
        }

        return $this->updateState;
    }

    /**
     * @return QueryBuilderState
     */
    public function upsert()
    {
        if (empty($this->upsertState)) {
            $this->upsertState = new QueryBuilderState();
        }

        return $this->upsertState;
    }

    /**
     * @return QueryBuilderState
     */
    public function delete()
    {
        if (empty($this->deleteState)) {
            $this->deleteState = new QueryBuilderState();
            $this->deleteState->setAliasIgnored(true);
        }

        return $this->deleteState;
    }

    /**
     * @return QueryBuilderState
     */
    public function groupOrderClause()
    {
        if (empty($this->groupOrderState)) {
            $this->groupOrderState = new QueryBuilderState();
            $this->groupOrderState->setAliasPreferred(true);
        }

        return $this->groupOrderState;
    }

    /**
     * @return QueryBuilderState
     */
    public function matchOperator()
    {
        if (empty($this->matchOperatorState)) {
            $this->matchOperatorState = new QueryBuilderState();
            $this->matchOperatorState->setWildcardsQuoted(true);
        }

        return $this->matchOperatorState;
    }
}
