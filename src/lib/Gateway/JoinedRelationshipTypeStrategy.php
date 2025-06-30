<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\CorePersistence\Gateway;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineRelationshipInterface;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataInterface;

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
        DoctrineSchemaMetadataInterface $relationshipMetadata,
        DoctrineRelationshipInterface $relationship,
        string $field,
        Comparison $comparison,
        QueryBuilder $queryBuilder,
        array $parameters
    ): RelationshipQuery {
        $parameterName = $field . '_' . count($parameters);

        $value = $comparison->getValue()->getValue();
        $type = $relationshipMetadata->getBindingTypeForColumn($field);
        if (is_array($value)) {
            $type += Connection::ARRAY_PARAM_OFFSET;
        }

        return new RelationshipQuery(
            new Parameter($parameterName, $value, $type),
            $queryBuilder
        );
    }
}
