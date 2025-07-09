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
interface RelationshipTypeStrategyRegistryInterface
{
    public function handleRelationshipType(
        QueryBuilder $queryBuilder,
        DoctrineRelationshipInterface $relationship,
        string $rootTableAlias,
        string $fromTable,
        string $toTable
    ): void;

    public function handleRelationshipTypeQuery(
        DoctrineRelationshipInterface $relationship,
        QueryBuilder $queryBuilder,
        string $fullColumnName,
        string $placeholder
    ): QueryBuilder;
}
