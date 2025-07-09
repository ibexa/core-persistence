<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\CorePersistence\Gateway;

use  Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineRelationshipInterface;
use LogicException;

/**
 * @internal
 */
final class SubSelectRelationshipTypeStrategy implements RelationshipTypeStrategyInterface
{
    public function handleRelationshipType(
        QueryBuilder $queryBuilder,
        DoctrineRelationshipInterface $relationship,
        string $rootTableAlias,
        string $fromTable,
        string $toTable
    ): void {
        if (empty($queryBuilder->getQueryPart('select'))) {
            $queryBuilder
                ->select($toTable . '.' . $relationship->getRelatedClassIdColumn())
                ->from($toTable);
        }

        if ($fromTable !== $rootTableAlias) {
            $queryBuilder->join(
                $fromTable,
                $toTable,
                $toTable,
                $queryBuilder->expr()->eq(
                    $fromTable . '.' . $relationship->getForeignKeyColumn(),
                    $toTable . '.' . $relationship->getRelatedClassIdColumn()
                )
            );
        }
    }

    public function handleRelationshipTypeQuery(
        QueryBuilder $queryBuilder,
        string $fullColumnName,
        string $placeholder
    ): QueryBuilder {
        if (empty($queryBuilder->getQueryPart('select'))) {
            throw new LogicException(
                'Query is not initialized.',
            );
        }

        $queryBuilder->andWhere(
            $queryBuilder->expr()->in($fullColumnName, $placeholder)
        );

        return $queryBuilder;
    }
}
