<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\CorePersistence\Gateway;

use Doctrine\DBAL\Query\Exception\NonUniqueAlias;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Query\QueryException;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineRelationshipInterface;

/**
 * @internal
 */
final class JoinedRelationshipTypeStrategy implements RelationshipTypeStrategyInterface
{
    public function handleRelationshipType(
        QueryBuilder $queryBuilder,
        DoctrineRelationshipInterface $relationship,
        string $rootTableAlias,
        string $fromTable,
        string $toTable
    ): void {
        $condition = (string)$queryBuilder->expr()->eq(
            $fromTable . '.' . $relationship->getForeignKeyColumn(),
            $toTable . '.' . $relationship->getRelatedClassIdColumn()
        );

        if ($this->isAliasAlreadyTaken($queryBuilder, $fromTable, $toTable, $condition)) {
            return;
        }

        $queryBuilder->leftJoin($fromTable, $toTable, $toTable, $condition);
    }

    public function handleRelationshipTypeQuery(
        QueryBuilder $queryBuilder,
        string $fullColumnName,
        string $placeholder
    ): QueryBuilder {
        return $queryBuilder;
    }

    /**
     * A gateway is free to join a relationship's table itself before handing the query over, so the
     * only safe answer comes from the query builder rather than from what this strategy has joined.
     * DBAL 4 exposes no accessor for the joins it holds, so the join is added to a copy and the copy
     * is asked to build itself: an alias that is already taken is exactly what NonUniqueAlias reports.
     */
    private function isAliasAlreadyTaken(
        QueryBuilder $queryBuilder,
        string $fromTable,
        string $toTable,
        string $condition
    ): bool {
        $probe = clone $queryBuilder;
        $probe->leftJoin($fromTable, $toTable, $toTable, $condition);

        try {
            $probe->getSQL();
        } catch (NonUniqueAlias) {
            return true;
        } catch (QueryException) {
            // The query cannot be built for an unrelated reason - an unknown FROM alias, or no
            // SELECT yet. Nothing is duplicated, so the join still has to be made, and the real
            // query builder will raise the same problem on its own terms.
            return false;
        }

        return false;
    }
}
