<?php

/* Icinga DB Web | (c) 2026 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model\Behavior;

use ipl\Orm\AliasedExpression;
use ipl\Orm\ColumnDefinition;
use ipl\Orm\Contract\QueryAwareBehavior;
use ipl\Orm\Contract\RewriteColumnBehavior;
use ipl\Orm\Query;
use ipl\Sql\Expression;
use ipl\Stdlib\Filter;
use ipl\Stdlib\Filter\Condition;

class CustomVarIsCompliant implements RewriteColumnBehavior, QueryAwareBehavior
{
    protected ?Query $query = null;

    public function __construct(protected string $alias, protected string $customVar, protected array $allowedValues)
    {
    }

    public function isSelectableColumn(string $name): bool
    {
        return $name === $this->alias;
    }

    public function rewriteColumn($column, ?string $relation = null): ?AliasedExpression
    {
        if ($column !== $this->alias) {
            return null;
        }

        list ($sql, $values) = $this->query->getDb()->getQueryBuilder()->assembleSelect(
            // TODO: Yes, this is how you make subqueries. But these are not the correct relations, just an example.
            // Instead, the subquery here must tell whether the host has a custom var $customVar with a value in $allowedValues.
            // This is probably a bit more complex.
            $this->query->createSubQuery(
                new Contract(),
                $this->query->getResolver()->qualifyPath('contracts', $this->query->getModel()->getTableAlias())
            )
                ->columns([new Expression('MIN(%s)', ['end'])])
                ->filter(Filter::all(Filter::equal('archived', false), Filter::equal('auto_renew', 0)))
                ->assembleSelect()
        );

        return new AliasedExpression($this->alias, "($sql)", null, ...$values);
    }

    public function rewriteColumnDefinition(ColumnDefinition $def, string $relation): void
    {
    }

    public function rewriteCondition(Condition $condition, $relation = null): void
    {
    }

    public function setQuery(Query $query): static
    {
        $this->query = $query;

        return $this;
    }
}
