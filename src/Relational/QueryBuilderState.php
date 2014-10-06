<?php
namespace Vda\Datasource\Relational;

class QueryBuilderState
{
    private $isRoot                = false;
    private $ignoreScope           = false;
    private $ignoreAlias           = false;
    private $preferAlias           = false;
    private $parenthesizeSubSelect = true;

    private static $rootState;
    private static $selectState;
    private static $insertState;
    private static $updateState;
    private static $deleteState;
    private static $groupOrderState;

    private function __construct()
    {
    }

    /**
     * @return self
     */
    public static function root()
    {
        if (empty(self::$rootState)) {
            self::$rootState = new self();
            self::$rootState->isRoot = true;
        }

        return self::$rootState;
    }

    /**
     * @return self
     */
    public static function select()
    {
        if (empty(self::$selectState)) {
            self::$selectState = new self();
        }

        return self::$selectState;
    }

    /**
     * @return self
     */
    public static function insert()
    {
        if (empty(self::$insertState)) {
            self::$insertState = new self();
            self::$insertState->ignoreScope = true;
            self::$insertState->ignoreAlias = true;
            self::$insertState->parenthesizeSubSelect = false;
        }

        return self::$insertState;
    }

    /**
     * @return self
     */
    public static function update()
    {
        if (empty(self::$updateState)) {
            self::$updateState = new self();
        }

        return self::$updateState;
    }

    /**
     * @return self
     */
    public static function delete()
    {
        if (empty(self::$deleteState)) {
            self::$deleteState = new self();
            self::$deleteState->ignoreAlias = true;
        }

        return self::$deleteState;
    }

    /**
     * @return self
     */
    public static function groupOrderClause()
    {
        if (empty(self::$groupOrderState)) {
            self::$groupOrderState = new self();
            self::$groupOrderState->preferAlias = true;
        }

        return self::$groupOrderState;
    }

    public function isRoot()
    {
        return $this->isRoot;
    }

    public function isScopeIgnored()
    {
        return $this->ignoreScope;
    }

    public function isAliasIgnored()
    {
        return $this->ignoreAlias;
    }

    public function isAliasPreferred()
    {
        return $this->preferAlias;
    }

    public function isSelectParenthesized()
    {
        return !$this->isRoot && $this->parenthesizeSubSelect;
    }
}
