<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\CorePersistence\Stub;

use Ibexa\Contracts\CorePersistence\Gateway\DoctrineRelationshipInterface;

final class InvalidDoctrineRelationship implements DoctrineRelationshipInterface
{
    public function getRelationshipClass(): string
    {
        return RelationshipClass::class;
    }

    public function getRelatedClassIdColumn(): string
    {
        return 'related_class_id_column';
    }

    public function getForeignProperty(): string
    {
        return 'foreign_property';
    }

    public function getForeignKeyColumn(): string
    {
        return 'foreign_key_column';
    }

    public function setJoinType(string $joinType): void
    {
        // Implementation here...
    }

    public function getJoinType(): string
    {
        return 'join_type';
    }
}
