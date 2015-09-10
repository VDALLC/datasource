<?php
namespace Vda\Datasource\Relational;

class QueryBuilderState
{
    private $isRoot                = false;
    private $ignoreScope           = false;
    private $ignoreAlias           = false;
    private $preferAlias           = false;
    private $parenthesizeSubSelect = true;
    private $quoteWildcards        = false;

    public function isRoot()
    {
        return $this->isRoot;
    }

    public function setRoot($isRoot)
    {
        $this->isRoot = $isRoot;
    }

    public function isScopeIgnored()
    {
        return $this->ignoreScope;
    }

    public function setScopeIgnored($ignoreScope)
    {
        $this->ignoreScope = $ignoreScope;
    }

    public function isAliasIgnored()
    {
        return $this->ignoreAlias;
    }

    public function setAliasIgnored($ignoreAlias)
    {
        $this->ignoreAlias = $ignoreAlias;
    }

    public function isAliasPreferred()
    {
        return $this->preferAlias;
    }

    public function setAliasPreferred($preferAlias)
    {
        $this->preferAlias = $preferAlias;
    }

    public function isSelectParenthesized()
    {
        return !$this->isRoot && $this->parenthesizeSubSelect;
    }

    public function setSelectParenthesized($parenthesizeSubSelect)
    {
        $this->parenthesizeSubSelect = $parenthesizeSubSelect;
    }

    public function isWildcardsQuoted()
    {
        return $this->quoteWildcards;
    }

    public function setWildcardsQuoted($quoteWildcards)
    {
        $this->quoteWildcards = $quoteWildcards;
    }
}
