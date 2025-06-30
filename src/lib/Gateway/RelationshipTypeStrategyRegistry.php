<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\CorePersistence\Gateway;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\CorePersistence\Exception\RuntimeMappingException;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineOneToManyRelationship;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineRelationship;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineRelationshipInterface;
use Ibexa\Contracts\CorePersistence\Gateway\DoctrineSchemaMetadataInterface;

final class RelationshipTypeStrategyRegistry implements RelationshipTypeStrategyRegistryInterface
{
    /** @var array<\Ibexa\CorePersistence\Gateway\RelationshipTypeStrategyInterface> */
    private array $strategies;

    public function __construct()
    {
        $this->strategies = [
            DoctrineRelationship::JOIN_TYPE_JOINED => new JoinedRelationshipTypeStrategy(),
            DoctrineRelationship::JOIN_TYPE_SUB_SELECT => new SubSelectRelationshipTypeStrategy(),
        ];
    }

    public function handleRelationshipType(
        QueryBuilder $queryBuilder,
        DoctrineRelationshipInterface $relationship,
        string $rootTableAlias,
        string $fromTable,
        string $toTable
    ): void {
        if (empty($this->strategies[$relationship->getJoinType()])) {
            throw new RuntimeMappingException(sprintf(
                'Unhandled relationship metadata. Expected one of "%s". Received "%s".',
                implode('", "', [
                    DoctrineRelationship::class,
                    DoctrineOneToManyRelationship::class,
                ]),
                get_class($relationship),
            ));
        }

        $this->strategies[$relationship->getJoinType()]->handleRelationshipType(
            $queryBuilder,
            $relationship,
            $rootTableAlias,
            $fromTable,
            $toTable
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
        if (empty($this->strategies[$relationship->getJoinType()])) {
            throw new RuntimeMappingException(sprintf(
                'Unhandled relationship metadata. Expected one of "%s". Received "%s".',
                implode('", "', [
                    DoctrineRelationship::class,
                    DoctrineOneToManyRelationship::class,
                ]),
                get_class($relationship),
            ));
        }

        return $this->strategies[$relationship->getJoinType()]->handleRelationshipTypeQuery(
            $relationshipMetadata,
            $relationship,
            $field,
            $comparison,
            $queryBuilder,
            $parameters
        );
    }
}
