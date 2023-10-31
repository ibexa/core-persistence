<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\CorePersistence\Gateway;

use LogicException;

final class DoctrineOneToManyRelationship extends AbstractDoctrineRelationship
{
    /**
     * @param class-string $relationshipClass
     * @param non-empty-string $foreignProperty
     * @param non-empty-string $relatedClassIdColumn
     */
    public function __construct(
        string $relationshipClass,
        string $foreignProperty,
        string $relatedClassIdColumn
    ) {
        $this->relationshipClass = $relationshipClass;
        $this->foreignProperty = $foreignProperty;
        $this->relatedClassIdColumn = $relatedClassIdColumn;
    }

    public function getForeignKeyColumn(): string
    {
        return '';
    }

    public function setJoinType(string $joinType): void
    {
        throw new LogicException('Intentionally not implemented.');
    }

    public function getJoinType(): string
    {
        return DoctrineRelationship::JOIN_TYPE_JOINED;
    }
}
