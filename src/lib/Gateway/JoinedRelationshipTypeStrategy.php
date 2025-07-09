<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\CorePersistence\Gateway;

use Doctrine\DBAL\Query\QueryBuilder;
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
        if ($this->isTableAlreadyJoined($queryBuilder, $toTable)) {
            return;
        }

        $queryBuilder->leftJoin(
            $fromTable,
            $toTable,
            $toTable,
            $queryBuilder->expr()->eq(
                $fromTable . '.' . $relationship->getForeignKeyColumn(),
                $toTable . '.' . $relationship->getRelatedClassIdColumn()
            )
        );
    }

    public function handleRelationshipTypeQuery(
        QueryBuilder $queryBuilder,
        string $fullColumnName,
        string $placeholder
    ): QueryBuilder {
        return $queryBuilder;
    }

    private function isTableAlreadyJoined(
        QueryBuilder $queryBuilder,
        string $tableToJoin
    ): bool {
        $joinQueryPart = $queryBuilder->getQueryPart('join');

        foreach ($joinQueryPart as $joins) {
            foreach ($joins as $join) {
                $joinAlias = $join['joinAlias'] ?? $join['joinTable'];

                if ($joinAlias === $tableToJoin) {
                    return true;
                }
            }
        }

        return false;
    }
}
