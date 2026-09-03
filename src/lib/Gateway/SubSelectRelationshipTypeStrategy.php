<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\CorePersistence\Gateway;

use Doctrine\DBAL\Query\Exception\NonUniqueAlias;
use Doctrine\DBAL\Query\Exception\UnknownAlias;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Query\QueryException;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineRelationshipInterface;
use LogicException;

/**
 * @internal
 */
final class SubSelectRelationshipTypeStrategy implements RelationshipTypeStrategyInterface
{
    private function isQueryInitialised(QueryBuilder $queryBuilder): bool
    {
        try {
            $queryBuilder->getSQL();

            return true;
        } catch (UnknownAlias | NonUniqueAlias) {
            return true;
        } catch (QueryException) {
            return false;
        }
    }

    public function handleRelationshipType(
        QueryBuilder $queryBuilder,
        DoctrineRelationshipInterface $relationship,
        string $rootTableAlias,
        string $fromTable,
        string $toTable
    ): void {
        if (!$this->isQueryInitialised($queryBuilder)) {
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
        if (!$this->isQueryInitialised($queryBuilder)) {
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
