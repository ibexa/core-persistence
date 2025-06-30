<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\CorePersistence\Gateway;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineRelationshipInterface;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataInterface;

/**
 * @internal
 */
interface RelationshipTypeStrategyRegistryInterface
{
    public function handleRelationshipType(
        QueryBuilder $queryBuilder,
        DoctrineRelationshipInterface $relationship,
        string $rootTableAlias,
        string $fromTable,
        string $toTable
    ): void;

    /**
     * @param array<\Ibexa\CorePersistence\Gateway\Parameter> $parameters
     */
    public function handleRelationshipTypeQuery(
        DoctrineSchemaMetadataInterface $relationshipMetadata,
        DoctrineRelationshipInterface $relationship,
        string $field,
        Comparison $comparison,
        QueryBuilder $queryBuilder,
        array $parameters
    ): RelationshipQuery;
}
