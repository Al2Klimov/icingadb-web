<?php

/* Icinga DB Web | (c) 2026 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model\Behavior;

use Icinga\Module\Icingadb\Model\CustomvarFlat;
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

        $subQuery = $this->query->createSubQuery(
            new CustomvarFlat(),
            $this->query->getResolver()->qualifyPath('customvar_flat', $this->query->getModel()->getTableAlias())
        );

        $subQuery->columns([new Expression('COUNT(*)')])
            ->filter(Filter::all(
                Filter::equal('flatname', $this->customVar),
                Filter::any(...array_map(
                    fn($value) => Filter::equal('flatvalue', $value),
                    $this->allowedValues
                ))
            ));

        list ($sql, $values) = $this->query->getDb()->getQueryBuilder()->assembleSelect(
            $subQuery->assembleSelect()
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
